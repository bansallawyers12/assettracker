<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportEntityScope;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\TrackingCategory;
use App\Services\ManualJournalEntryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualJournalEntryController extends Controller
{
    use ResolvesReportEntityScope;

    public function __construct(private ManualJournalEntryService $manualJournalService) {}

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return redirect()->route('financial-reports.journal-entries.create')
                ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
        }

        $businessEntity = BusinessEntity::query()->find($entityIds[0]);
        if (! $businessEntity) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'No reporting entities are available.');
        }

        $this->authorize('update', $businessEntity);

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        $trackingCategories = TrackingCategory::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['activeSubCategories'])
            ->get();

        return view('financial-reports.journal-entry-create', [
            'businessEntity' => $businessEntity,
            'businessEntities' => $businessEntities,
            'accounts' => $accounts,
            'trackingCategories' => $trackingCategories,
            'entryDate' => old('entry_date', now()->toDateString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:50',
            'lines' => 'required|array|min:2|max:40',
            'lines.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.tracking_category_id' => 'nullable|integer|exists:tracking_categories,id',
            'lines.*.tracking_sub_category_id' => 'nullable|integer|exists:tracking_sub_categories,id',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorize('update', $businessEntity);

        $lines = [];
        foreach ($validated['lines'] as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }
            $lines[] = [
                'chart_of_account_id' => (int) $line['chart_of_account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'tracking_category_id' => $line['tracking_category_id'] ?? null,
                'tracking_sub_category_id' => $line['tracking_sub_category_id'] ?? null,
            ];
        }

        if (count($lines) < 2) {
            return back()->withInput()->with('error', 'Enter at least two lines with debits or credits.');
        }

        try {
            $entry = $this->manualJournalService->post(
                $businessEntity,
                Carbon::parse($validated['entry_date'])->toDateString(),
                $validated['description'],
                $lines,
                $validated['reference_number'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('financial-reports.account-transactions', [
                'scope' => 'selected',
                'entity_ids' => [$businessEntity->id],
                'start_date' => $entry->entry_date->toDateString(),
                'end_date' => $entry->entry_date->toDateString(),
            ])
            ->with('success', 'Journal entry '.$entry->reference_number.' posted.');
    }

    public function storeOpeningBalances(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'as_of_date' => 'required|date',
            'balances' => 'required|array',
            'balances.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'balances.*.amount' => 'nullable|numeric',
        ]);

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorize('update', $businessEntity);

        $asOfDate = Carbon::parse($validated['as_of_date'])->toDateString();
        $posted = 0;
        $errors = [];

        foreach ($validated['balances'] as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if (abs($amount) < 0.00001) {
                continue;
            }

            $account = ChartOfAccount::query()->findOrFail((int) $row['chart_of_account_id']);

            try {
                $this->manualJournalService->postOpeningBalance($businessEntity, $account, $amount, $asOfDate);
                $posted++;
            } catch (\Throwable $e) {
                $errors[] = $account->account_code.': '.$e->getMessage();
            }
        }

        if ($posted === 0) {
            $message = $errors !== []
                ? implode(' ', $errors)
                : 'Enter at least one non-zero opening balance.';

            return back()->with('error', $message);
        }

        $success = "Posted {$posted} opening balance journal(s).";
        if ($errors !== []) {
            $success .= ' Some rows were skipped: '.implode(' ', $errors);
        }

        return redirect()
            ->route('financial-reports.balance-sheet', [
                'scope' => 'selected',
                'entity_ids' => [$businessEntity->id],
                'as_of_date' => $asOfDate,
            ])
            ->with('success', $success);
    }
}
