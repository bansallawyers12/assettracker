<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportEntityScope;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\TrackingCategory;
use App\Models\TrackingSubCategory;
use App\Services\ManualJournalEntryService;
use App\Support\FinancialYear;
use App\Support\ManualJournalRegister;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManualJournalEntryController extends Controller
{
    use ResolvesReportEntityScope;

    public function __construct(
        private ManualJournalEntryService $manualJournalService,
        private ManualJournalRegister $register,
    ) {}

    public function indexHub(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($entityIds === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $report = $this->buildIndexReport($request, $entityIds, 'all');
        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        return view('financial-reports.journal-entries-index', [
            'report' => $report,
            'businessEntities' => $businessEntities,
            'entityScoped' => false,
            'routes' => $this->hubRoutes(),
        ]);
    }

    public function index(BusinessEntity $businessEntity, Request $request): View|RedirectResponse
    {
        $this->authorize('view', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        $request->merge([
            'scope' => 'selected',
            'entity_ids' => [(int) $businessEntity->id],
        ]);

        $report = $this->buildIndexReport($request, [(int) $businessEntity->id], 'selected');
        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        return view('financial-reports.journal-entries-index', [
            'report' => $report,
            'businessEntities' => $businessEntities,
            'entityScoped' => true,
            'routes' => $this->entityRoutes($businessEntity),
        ]);
    }

    public function showHub(JournalEntry $journalEntry, Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($entityIds === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        $entry = $this->register->findVisibleEntry((int) $journalEntry->id, $entityIds);
        if (! $entry) {
            abort(404);
        }

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $report = $this->mergeReportFormScope([
            'business_entity' => $entry->businessEntity,
            'business_entities' => collect([$entry->businessEntity]),
            'is_consolidated' => false,
        ], $request, $entityIds);

        return view('financial-reports.journal-entry-show', [
            'entry' => $entry,
            'report' => $report,
            'businessEntities' => $businessEntities,
            'entityScoped' => false,
            'routes' => $this->hubRoutes(),
        ]);
    }

    public function show(BusinessEntity $businessEntity, JournalEntry $journalEntry): View|RedirectResponse
    {
        $this->authorize('view', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        if ((int) $journalEntry->business_entity_id !== (int) $businessEntity->id
            || $journalEntry->source_type !== null
            || ! $journalEntry->is_posted) {
            abort(404);
        }

        $entry = $this->register->findVisibleEntry((int) $journalEntry->id, [(int) $businessEntity->id]);
        if (! $entry) {
            abort(404);
        }

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();

        return view('financial-reports.journal-entry-show', [
            'entry' => $entry,
            'report' => [
                'business_entity' => $businessEntity,
                'business_entities' => collect([$businessEntity]),
                'is_consolidated' => false,
                'forms_scope' => 'selected',
                'forms_entity_ids' => [(int) $businessEntity->id],
            ],
            'businessEntities' => $businessEntities,
            'entityScoped' => true,
            'routes' => $this->entityRoutes($businessEntity),
        ]);
    }

    public function createForEntity(BusinessEntity $businessEntity, Request $request): View|RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        return $this->renderCreateForm($businessEntity, true, $this->entityRoutes($businessEntity));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return redirect()->route('financial-reports.journal-entries.index')
                ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
        }

        if ($entityIds === []) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'No reporting entities are available.');
        }

        $pickerEntityIds = $entityIds;
        $prefillEntityId = (int) $request->query('prefill_entity_id', 0);
        if ($prefillEntityId > 0 && in_array($prefillEntityId, $entityIds, true)) {
            $entityIds = [$prefillEntityId];
        }

        $businessEntity = BusinessEntity::query()->find($entityIds[0]);
        if (! $businessEntity) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'No reporting entities are available.');
        }

        $this->authorize('update', $businessEntity);

        return $this->renderCreateForm($businessEntity, false, $this->hubRoutes(), $request, $pickerEntityIds);
    }

    public function storeForEntity(BusinessEntity $businessEntity, Request $request): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        $request->merge(['business_entity_id' => $businessEntity->id]);

        return $this->storeJournal($request, $this->entityRoutes($businessEntity));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->storeJournal($request, $this->hubRoutes());
    }

    public function storeOpeningBalancesForEntity(BusinessEntity $businessEntity, Request $request): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        $request->merge(['business_entity_id' => $businessEntity->id]);

        return $this->persistOpeningBalances($request, $this->entityRoutes($businessEntity));
    }

    public function storeOpeningBalances(Request $request): RedirectResponse
    {
        return $this->persistOpeningBalances($request, $this->hubRoutes());
    }

    public function editForEntity(BusinessEntity $businessEntity, JournalEntry $journalEntry): View|RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        $entry = $this->editableEntryOrAbort($journalEntry, [(int) $businessEntity->id], $businessEntity);

        return $this->renderCreateForm(
            $businessEntity,
            true,
            $this->entityRoutes($businessEntity),
            editing: $entry
        );
    }

    public function editHub(JournalEntry $journalEntry, Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->requireReportEntityIds($request);
        if ($entityIds instanceof RedirectResponse) {
            return $entityIds;
        }

        $entry = $this->editableEntryOrAbort($journalEntry, $entityIds);
        $this->authorize('update', $entry->businessEntity);

        return $this->renderCreateForm(
            $entry->businessEntity,
            false,
            $this->hubRoutes(),
            $request,
            $entityIds,
            $entry
        );
    }

    public function updateForEntity(BusinessEntity $businessEntity, JournalEntry $journalEntry, Request $request): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        $request->merge(['business_entity_id' => $businessEntity->id]);

        return $this->updateJournal($request, $journalEntry, [(int) $businessEntity->id], $this->entityRoutes($businessEntity));
    }

    public function updateHub(JournalEntry $journalEntry, Request $request): RedirectResponse
    {
        $entityIds = $this->requireReportEntityIds($request);
        if ($entityIds instanceof RedirectResponse) {
            return $entityIds;
        }

        return $this->updateJournal($request, $journalEntry, $entityIds, $this->hubRoutes());
    }

    public function reverseForEntity(BusinessEntity $businessEntity, JournalEntry $journalEntry, Request $request): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        return $this->reverseJournal($request, $journalEntry, [(int) $businessEntity->id], $this->entityRoutes($businessEntity));
    }

    public function reverseHub(JournalEntry $journalEntry, Request $request): RedirectResponse
    {
        $entityIds = $this->requireReportEntityIds($request);
        if ($entityIds instanceof RedirectResponse) {
            return $entityIds;
        }

        return $this->reverseJournal($request, $journalEntry, $entityIds, $this->hubRoutes());
    }

    public function voidForEntity(BusinessEntity $businessEntity, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ($redirect = $this->redirectIfExcludedFromFinancialReports($businessEntity)) {
            return $redirect;
        }

        return $this->voidJournal($journalEntry, [(int) $businessEntity->id], $this->entityRoutes($businessEntity));
    }

    public function voidHub(JournalEntry $journalEntry, Request $request): RedirectResponse
    {
        $entityIds = $this->requireReportEntityIds($request);
        if ($entityIds instanceof RedirectResponse) {
            return $entityIds;
        }

        return $this->voidJournal($journalEntry, $entityIds, $this->hubRoutes());
    }

    /**
     * @param  array<string, mixed>  $routes
     */
    private function storeJournal(Request $request, array $routes): RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $validated = $request->validate($this->journalRules());

        $businessEntity = BusinessEntity::query()->findOrFail((int) $validated['business_entity_id']);
        $this->authorize('update', $businessEntity);

        $lines = $this->normalizeSubmittedLines($validated['lines'], $businessEntity);
        if ($lines instanceof RedirectResponse) {
            return $lines;
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
            ->to($this->showUrlForEntry($routes, $entry))
            ->with('success', 'Journal entry '.$entry->reference_number.' posted.');
    }

    /**
     * @param  array<string, mixed>  $routes
     */
    private function persistOpeningBalances(Request $request, array $routes): RedirectResponse
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

        $indexParams = [
            'start_date' => $asOfDate,
            'end_date' => $asOfDate,
            'type' => ManualJournalRegister::TYPE_OPENING,
        ];

        if (($routes['show'] ?? '') === 'financial-reports.journal-entries.show') {
            $indexParams = array_merge($this->scopeQueryForCreate($request), $indexParams);
        }

        return redirect()
            ->to($routes['index'].'?'.http_build_query($indexParams))
            ->with('success', $success);
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function buildIndexReport(Request $request, array $entityIds, string $defaultScope): array
    {
        $startDate = Carbon::parse(
            $request->get('start_date', FinancialYear::currentStart()->toDateString())
        )->toDateString();
        $endDate = Carbon::parse(
            $request->get('end_date', FinancialYear::currentEnd()->toDateString())
        )->toDateString();
        $typeFilter = (string) $request->get('type', ManualJournalRegister::TYPE_ALL);

        $report = $this->register->buildIndexReport(
            $entityIds,
            $startDate,
            $endDate,
            $typeFilter,
            (string) $request->input('scope', $defaultScope),
        );

        return $this->mergeReportFormScope($report, $request, $entityIds);
    }

    /**
     * @param  array<string, mixed>  $routes
     * @param  array<int>|null  $scopedEntityIds
     */
    private function renderCreateForm(
        BusinessEntity $businessEntity,
        bool $entityScoped,
        array $routes,
        ?Request $request = null,
        ?array $scopedEntityIds = null,
        ?JournalEntry $editing = null,
    ): View {
        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $pickerEntities = $scopedEntityIds === null
            ? $businessEntities
            : $businessEntities->whereIn('id', $scopedEntityIds)->values();

        $trackingCategories = TrackingCategory::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['activeSubCategories'])
            ->get();

        $scopeQuery = [];
        if ($request && ! $entityScoped) {
            $scopeQuery = $this->scopeQueryForCreate($request);
        }

        $entryDate = old('entry_date', $editing?->entry_date?->toDateString() ?? now()->toDateString());

        return view('financial-reports.journal-entry-create', [
            'businessEntity' => $businessEntity,
            'businessEntities' => $pickerEntities,
            'accounts' => $accounts,
            'trackingCategories' => $trackingCategories,
            'entryDate' => $entryDate,
            'entityScoped' => $entityScoped,
            'routes' => $routes,
            'scopeQuery' => $scopeQuery,
            'editing' => $editing,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function hubRoutes(): array
    {
        return [
            'index' => route('financial-reports.journal-entries.index'),
            'create' => route('financial-reports.journal-entries.create'),
            'store' => route('financial-reports.journal-entries.store'),
            'openingBalancesStore' => route('financial-reports.opening-balances.store'),
            'show' => 'financial-reports.journal-entries.show',
            'edit' => 'financial-reports.journal-entries.edit',
            'update' => 'financial-reports.journal-entries.update',
            'reverse' => 'financial-reports.journal-entries.reverse',
            'void' => 'financial-reports.journal-entries.void',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entityRoutes(BusinessEntity $businessEntity): array
    {
        return [
            'index' => route('business-entities.financial-reports.journal-entries.index', $businessEntity),
            'create' => route('business-entities.financial-reports.journal-entries.create', $businessEntity),
            'store' => route('business-entities.financial-reports.journal-entries.store', $businessEntity),
            'openingBalancesStore' => route('business-entities.financial-reports.opening-balances.store', $businessEntity),
            'show' => 'business-entities.financial-reports.journal-entries.show',
            'edit' => 'business-entities.financial-reports.journal-entries.edit',
            'update' => 'business-entities.financial-reports.journal-entries.update',
            'reverse' => 'business-entities.financial-reports.journal-entries.reverse',
            'void' => 'business-entities.financial-reports.journal-entries.void',
            'entity' => $businessEntity,
        ];
    }

    /**
     * @param  array<string, mixed>  $routes
     */
    private function showUrlForEntry(array $routes, JournalEntry $entry): string
    {
        if (($routes['show'] ?? '') === 'business-entities.financial-reports.journal-entries.show') {
            return route($routes['show'], [
                'businessEntity' => $routes['entity'] ?? $entry->business_entity_id,
                'journalEntry' => $entry,
            ]);
        }

        return route('financial-reports.journal-entries.show', [
            'journalEntry' => $entry,
            'scope' => 'selected',
            'entity_ids' => [(int) $entry->business_entity_id],
        ]);
    }

    /**
     * @param  array<int>  $entityIds
     * @param  array<string, mixed>  $routes
     */
    private function updateJournal(Request $request, JournalEntry $journalEntry, array $entityIds, array $routes): RedirectResponse
    {
        $entry = $this->editableEntryOrAbort($journalEntry, $entityIds);
        $this->authorize('update', $entry->businessEntity);

        $validated = $request->validate($this->journalRules($entry));
        $businessEntity = $entry->businessEntity;
        $lines = $this->normalizeSubmittedLines($validated['lines'], $businessEntity);
        if ($lines instanceof RedirectResponse) {
            return $lines;
        }

        try {
            $updated = $this->manualJournalService->update(
                $entry,
                Carbon::parse($validated['entry_date'])->toDateString(),
                $validated['description'],
                $lines,
                $validated['reference_number'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->to($this->showUrlForEntry($routes, $updated))
            ->with('success', 'Journal entry '.$updated->reference_number.' updated.');
    }

    /**
     * @param  array<int>  $entityIds
     * @param  array<string, mixed>  $routes
     */
    private function reverseJournal(Request $request, JournalEntry $journalEntry, array $entityIds, array $routes): RedirectResponse
    {
        $entry = $this->visibleManualOrAbort($journalEntry, $entityIds);
        $this->authorize('update', $entry->businessEntity);

        $validated = $request->validate([
            'entry_date' => 'required|date',
        ]);

        try {
            $reversal = $this->manualJournalService->reverse(
                $entry,
                Carbon::parse($validated['entry_date'])->toDateString()
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->to($this->showUrlForEntry($routes, $reversal))
            ->with('success', 'Reversal '.$reversal->reference_number.' posted.');
    }

    /**
     * @param  array<int>  $entityIds
     * @param  array<string, mixed>  $routes
     */
    private function voidJournal(JournalEntry $journalEntry, array $entityIds, array $routes): RedirectResponse
    {
        $entry = $this->visibleManualOrAbort($journalEntry, $entityIds);
        $this->authorize('update', $entry->businessEntity);

        try {
            $offset = $this->manualJournalService->void($entry);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->to($this->showUrlForEntry($routes, $entry->fresh() ?? $entry))
            ->with('success', 'Journal voided. Offset '.$offset->reference_number.' posted on the original date.');
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function visibleManualOrAbort(JournalEntry $journalEntry, array $entityIds): JournalEntry
    {
        $entry = $this->register->findVisibleEntry((int) $journalEntry->id, $entityIds);
        if (! $entry) {
            abort(404);
        }

        return $entry;
    }

    /**
     * @param  array<int>  $entityIds
     */
    private function editableEntryOrAbort(JournalEntry $journalEntry, array $entityIds, ?BusinessEntity $businessEntity = null): JournalEntry
    {
        $entry = $this->visibleManualOrAbort($journalEntry, $entityIds);

        if ($businessEntity && (int) $entry->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }

        if (! $entry->canEdit()) {
            abort(403, 'This journal cannot be edited.');
        }

        return $entry;
    }

    /**
     * @return array<int>|RedirectResponse
     */
    private function requireReportEntityIds(Request $request): array|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return $this->redirectInvalidReportScope();
        }
        if ($entityIds === []) {
            return redirect()->route('financial-reports.index')->with('error', 'No reporting entities are available.');
        }

        return $entityIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function journalRules(?JournalEntry $ignore = null): array
    {
        $referenceRule = ['nullable', 'string', 'max:50'];
        if ($ignore) {
            $referenceRule[] = Rule::unique('journal_entries', 'reference_number')->ignore($ignore->id);
        }

        return [
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference_number' => $referenceRule,
            'lines' => 'required|array|min:2|max:40',
            'lines.*.chart_of_account_id' => 'nullable|integer|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.tracking_category_id' => 'nullable|integer|exists:tracking_categories,id',
            'lines.*.tracking_sub_category_id' => 'nullable|integer|exists:tracking_sub_categories,id',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rawLines
     * @return list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>|RedirectResponse
     */
    private function normalizeSubmittedLines(array $rawLines, BusinessEntity $businessEntity): array|RedirectResponse
    {
        $lines = [];
        foreach ($rawLines as $line) {
            $accountId = (int) ($line['chart_of_account_id'] ?? 0);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($accountId <= 0 || ($debit === 0.0 && $credit === 0.0)) {
                continue;
            }

            $trackingCategoryId = isset($line['tracking_category_id']) && $line['tracking_category_id'] !== ''
                ? (int) $line['tracking_category_id']
                : null;
            $trackingSubCategoryId = isset($line['tracking_sub_category_id']) && $line['tracking_sub_category_id'] !== ''
                ? (int) $line['tracking_sub_category_id']
                : null;

            if ($trackingSubCategoryId && ! $trackingCategoryId) {
                $subCategory = TrackingSubCategory::query()->find($trackingSubCategoryId);
                $trackingCategoryId = $subCategory?->tracking_category_id;
            }

            $lines[] = [
                'chart_of_account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'tracking_category_id' => $trackingCategoryId,
                'tracking_sub_category_id' => $trackingSubCategoryId,
            ];
        }

        if (count($lines) < 2) {
            return back()->withInput()->with('error', 'Enter at least two lines with debits or credits.');
        }

        if ($error = $this->validateManualJournalTracking($businessEntity, $lines)) {
            return back()->withInput()->with('error', $error);
        }

        return $lines;
    }

    protected function redirectIfExcludedFromFinancialReports(BusinessEntity $businessEntity): ?RedirectResponse
    {
        if ($businessEntity->isTenancyContactOnly()) {
            return redirect()->route('financial-reports.index')
                ->with('error', 'Financial reports are not available for this company because it is excluded from reporting (for example, a property manager kept for contact purposes only).');
        }

        return null;
    }

    protected function redirectInvalidReportScope(): RedirectResponse
    {
        return redirect()->route('financial-reports.journal-entries.index')
            ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $lines
     */
    private function validateManualJournalTracking(BusinessEntity $businessEntity, array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            $categoryId = $line['tracking_category_id'] ?? null;
            $subCategoryId = $line['tracking_sub_category_id'] ?? null;

            if ($categoryId) {
                $category = TrackingCategory::query()->find((int) $categoryId);
                if (! $category || (int) $category->business_entity_id !== (int) $businessEntity->id) {
                    return "Line {$lineNum}: tracking category does not belong to this entity.";
                }
            }

            if ($subCategoryId) {
                $subCategory = TrackingSubCategory::query()
                    ->with('trackingCategory')
                    ->find((int) $subCategoryId);

                if (! $subCategory || (int) $subCategory->trackingCategory?->business_entity_id !== (int) $businessEntity->id) {
                    return "Line {$lineNum}: tracking sub-category does not belong to this entity.";
                }

                if ($categoryId && (int) $subCategory->tracking_category_id !== (int) $categoryId) {
                    return "Line {$lineNum}: tracking sub-category does not match the selected category.";
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function scopeQueryForCreate(Request $request): array
    {
        $query = ['scope' => (string) $request->input('scope', 'all')];
        if ($query['scope'] === 'selected') {
            foreach ((array) $request->input('entity_ids', []) as $id) {
                $query['entity_ids'][] = (int) $id;
            }
        }

        return $query;
    }
}
