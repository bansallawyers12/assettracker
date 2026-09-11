<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Mail\InvoiceReminderMail;
use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\BankStatementMatchSuggester;
use App\Services\InvoicePaymentService;
use App\Services\InvoicePostingService;
use App\Support\TableSort;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function index(Request $request, ?BusinessEntity $businessEntity = null)
    {
        $tableSort = TableSort::resolve(
            $request,
            ['number', 'entity', 'customer', 'issue', 'total', 'status'],
            'issue',
            'desc'
        );

        $statusFilter = $request->string('status')->toString();
        $receivableOnly = $request->boolean('receivable');
        $assetIdFilter = $request->integer('asset_id') ?: null;
        $leaseIdFilter = $request->integer('lease_id') ?: null;

        if ($businessEntity) {
            $this->authorize('view', $businessEntity);
            $this->ensureOperationalForAccounting($businessEntity);

            $query = Invoice::where('business_entity_id', $businessEntity->id)->with(['asset', 'lease']);
            $this->applyInvoiceListFilters($query, $statusFilter, $receivableOnly, $assetIdFilter, $leaseIdFilter);
            $tableSort->applyToQuery($query, [
                'number' => 'invoice_number',
                'customer' => 'customer_name',
                'issue' => 'issue_date',
                'total' => 'total_amount',
                'status' => 'status',
            ], 'issue');

            $invoices = $query->paginate(20)->withQueryString();
            $filterAssets = Asset::query()
                ->where('business_entity_id', $businessEntity->id)
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('invoices.index', compact(
                'businessEntity',
                'invoices',
                'tableSort',
                'statusFilter',
                'receivableOnly',
                'assetIdFilter',
                'leaseIdFilter',
                'filterAssets'
            ));
        }

        $query = Invoice::query()
            ->whereIn('business_entity_id', BusinessEntity::query()->operationalEntities()->pluck('id'))
            ->with(['asset', 'businessEntity', 'lease']);

        $this->applyInvoiceListFilters($query, $statusFilter, $receivableOnly, $assetIdFilter, $leaseIdFilter);

        if ($tableSort->column === 'entity') {
            $query->join('business_entities', 'invoices.business_entity_id', '=', 'business_entities.id')
                ->select('invoices.*')
                ->orderBy('business_entities.legal_name', $tableSort->order)
                ->orderByDesc('invoices.issue_date');
        } else {
            $tableSort->applyToQuery($query, [
                'number' => 'invoice_number',
                'customer' => 'customer_name',
                'issue' => 'issue_date',
                'total' => 'total_amount',
                'status' => 'status',
            ], 'issue');
        }

        $invoices = $query->paginate(30)->withQueryString();
        $filterAssets = collect();

        return view('invoices.index', compact(
            'invoices',
            'tableSort',
            'statusFilter',
            'receivableOnly',
            'assetIdFilter',
            'leaseIdFilter',
            'filterAssets'
        ));
    }

    public function create(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $issueDate = old('issue_date', now()->toDateString());
        $suggestedInvoiceNumber = old('invoice_number', Invoice::suggestNumber($businessEntity, $issueDate));
        $defaultDueDate = old('due_date', Carbon::parse($issueDate)->addDays(30)->toDateString());

        return view('invoices.create', array_merge(
            $this->invoiceFormContext($businessEntity),
            [
                'businessEntity' => $businessEntity,
                'suggestedInvoiceNumber' => $suggestedInvoiceNumber,
                'defaultDueDate' => $defaultDueDate,
                'issueDate' => $issueDate,
                'invoice' => null,
                'lockInvoiceNumber' => false,
                'lockDueDate' => false,
            ]
        ));
    }

    public function suggestNumber(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $data = $request->validate([
            'issue_date' => ['required', 'date'],
        ]);

        return response()->json([
            'invoice_number' => Invoice::suggestNumber($businessEntity, $data['issue_date']),
        ]);
    }

    public function store(Request $request, BusinessEntity $businessEntity, InvoicePostingService $postingService)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $lineAccountCodes = $this->lineAccountCodes();
        $data = $this->validateInvoicePayload($request, $businessEntity, $lineAccountCodes);
        [$assetId, $leaseId] = $this->resolveAssetAndLease($businessEntity, $data);

        $gstRate = round(((float) $data['gst_percent']) / 100, 4);
        $gstBasis = $data['gst_basis'];
        if ($gstBasis === 'none') {
            $gstRate = 0.0;
        }
        $saveAndPost = $request->boolean('save_and_post');

        try {
            $invoice = DB::transaction(function () use ($businessEntity, $data, $assetId, $leaseId, $gstRate, $gstBasis, $saveAndPost, $postingService) {
                $invoice = new Invoice;
                $invoice->fill([
                    'invoice_number' => $data['invoice_number'],
                    'issue_date' => $data['issue_date'],
                    'due_date' => $data['due_date'] ?? Carbon::parse($data['issue_date'])->addDays(30)->toDateString(),
                    'customer_name' => $data['customer_name'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'currency' => $data['currency'] ?? 'AUD',
                    'gst_basis' => $gstBasis,
                ]);
                $invoice->business_entity_id = $businessEntity->id;
                $invoice->asset_id = $assetId;
                $invoice->lease_id = $leaseId;
                $invoice->status = 'draft';
                $invoice->is_posted = false;
                $invoice->subtotal = 0;
                $invoice->gst_amount = 0;
                $invoice->total_amount = 0;
                $invoice->save();

                $this->syncInvoiceLines($invoice, $data['lines'], $gstRate, $gstBasis);

                $invoice->load('lines');
                if ($saveAndPost) {
                    $postingService->post($invoice);
                }

                return $invoice;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Invoice was not saved: '.$e->getMessage());
        }

        $message = $saveAndPost ? 'Invoice created and posted to ledger' : 'Invoice created';

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', $message);
    }

    public function show(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('view', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        $invoice->load([
            'lines',
            'lease.tenant',
            'asset',
            'paymentAllocations.transaction.bankAccount',
            'paymentAllocations.transaction.bankStatementEntries',
            'paymentTransaction.bankAccount',
            'paymentTransaction.bankStatementEntries',
        ]);

        $paymentBankAccounts = collect();
        $unmatchedStatementEntries = collect();
        $suggestedStatementEntryId = null;
        $suggestedPaymentBankAccountId = null;
        $amountDue = $invoice->amountDue();
        $canRecordPayment = in_array($invoice->status, ['approved', 'partial'], true) && $amountDue > 0.005;

        if ($canRecordPayment) {
            $paymentBankAccounts = $businessEntity->bankAccountLinksForDisplay()
                ->map(fn ($link) => $link->bankAccount)
                ->filter()
                ->unique('id')
                ->filter(fn (BankAccount $account) => $account->canUseForTransaction($businessEntity))
                ->sortBy('account_name')
                ->values();

            if ($paymentBankAccounts->isNotEmpty()) {
                $unmatchedStatementEntries = BankStatementEntry::query()
                    ->whereIn('bank_account_id', $paymentBankAccounts->pluck('id'))
                    ->whereNull('transaction_id')
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get()
                    ->sortByDesc(function (BankStatementEntry $entry) use ($amountDue) {
                        $amount = (float) $entry->amount;
                        if ($amount <= 0) {
                            return -1;
                        }

                        if (abs($amount - $amountDue) <= BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
                            return 2;
                        }

                        return $amount <= $amountDue + BankStatementMatchSuggester::AMOUNT_TOLERANCE
                            ? 1
                            : 0;
                    })
                    ->values();

                $suggestedEntry = $unmatchedStatementEntries
                    ->first(function (BankStatementEntry $entry) use ($amountDue) {
                        $amount = (float) $entry->amount;

                        return $amount > 0
                            && abs($amount - $amountDue) <= BankStatementMatchSuggester::AMOUNT_TOLERANCE;
                    });

                $suggestedStatementEntryId = $suggestedEntry?->id;
                $suggestedPaymentBankAccountId = $suggestedEntry?->bank_account_id;
            }
        }

        return view('invoices.show', compact(
            'businessEntity',
            'invoice',
            'paymentBankAccounts',
            'unmatchedStatementEntries',
            'suggestedStatementEntryId',
            'suggestedPaymentBankAccountId',
            'amountDue',
            'canRecordPayment'
        ));
    }

    /**
     * Printable invoice page (use browser Print → Save as PDF).
     */
    public function download(BusinessEntity $businessEntity, Invoice $invoice): View|Response
    {
        $this->authorize('view', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        $invoice->load(['lines', 'lease.tenant', 'asset', 'businessEntity']);

        $filename = Str::of($invoice->invoice_number)
            ->replaceMatches('/[^\w.\-]+/', '-')
            ->trim('-')
            ->append('.html')
            ->toString();

        return response()
            ->view('invoices.print', compact('businessEntity', 'invoice'))
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    public function edit(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        if ($invoice->is_posted) {
            return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
                ->with('error', 'Posted invoices cannot be edited.');
        }

        $invoice->load('lines');

        $issueDate = old('issue_date', $invoice->issue_date->toDateString());
        $suggestedInvoiceNumber = old('invoice_number', $invoice->invoice_number);
        $defaultDueDate = old('due_date', optional($invoice->due_date)?->toDateString()
            ?? Carbon::parse($issueDate)->addDays(30)->toDateString());

        $gstPercent = old(
            'gst_percent',
            round(((float) ($invoice->lines->first()?->gst_rate ?? 0.1)) * 100, 2)
        );

        return view('invoices.edit', array_merge(
            $this->invoiceFormContext($businessEntity),
            [
                'businessEntity' => $businessEntity,
                'invoice' => $invoice,
                'suggestedInvoiceNumber' => $suggestedInvoiceNumber,
                'defaultDueDate' => $defaultDueDate,
                'issueDate' => $issueDate,
                'defaultGstPercent' => $gstPercent,
                // Existing drafts keep their number/due date unless the user edits them.
                'lockInvoiceNumber' => true,
                'lockDueDate' => true,
            ]
        ));
    }

    public function update(Request $request, BusinessEntity $businessEntity, Invoice $invoice, InvoicePostingService $postingService)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        if ($invoice->is_posted) {
            return back()->with('error', 'Posted invoices cannot be edited.');
        }

        $lineAccountCodes = $this->lineAccountCodes();
        $data = $this->validateInvoicePayload($request, $businessEntity, $lineAccountCodes, $invoice);
        [$assetId, $leaseId] = $this->resolveAssetAndLease($businessEntity, $data);

        $gstRate = round(((float) $data['gst_percent']) / 100, 4);
        $gstBasis = $data['gst_basis'];
        if ($gstBasis === 'none') {
            $gstRate = 0.0;
        }
        $saveAndPost = $request->boolean('save_and_post');

        try {
            DB::transaction(function () use ($invoice, $data, $assetId, $leaseId, $gstRate, $gstBasis, $saveAndPost, $postingService) {
                $invoice->fill([
                    'invoice_number' => $data['invoice_number'],
                    'issue_date' => $data['issue_date'],
                    'due_date' => $data['due_date'] ?? Carbon::parse($data['issue_date'])->addDays(30)->toDateString(),
                    'customer_name' => $data['customer_name'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'currency' => $data['currency'] ?? $invoice->currency ?? 'AUD',
                    'gst_basis' => $gstBasis,
                ]);
                $invoice->asset_id = $assetId;
                $invoice->lease_id = $leaseId;
                $invoice->save();

                $invoice->lines()->delete();
                $this->syncInvoiceLines($invoice, $data['lines'], $gstRate, $gstBasis);
                $invoice->load('lines');

                if ($saveAndPost) {
                    $postingService->post($invoice);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Invoice was not saved: '.$e->getMessage());
        }

        $invoice->refresh()->load('lines');
        $message = $saveAndPost ? 'Invoice updated and posted to ledger' : 'Invoice updated';

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', $message);
    }

    public function destroy(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        if ($invoice->is_posted) {
            return back()->with('error', 'Posted invoices cannot be deleted.');
        }
        $invoice->delete();

        return redirect()->route('business-entities.invoices.index', $businessEntity)
            ->with('success', 'Invoice deleted');
    }

    /**
     * Browsers issue GET when this URL is opened directly; posting requires POST from the invoice page.
     */
    public function postRedirect(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('view', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('info', 'Use the Post to ledger button on this page to post the invoice.');
    }

    public function post(BusinessEntity $businessEntity, Invoice $invoice, InvoicePostingService $postingService)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        if ($invoice->is_posted) {
            return back()->with('info', 'Invoice already posted.');
        }
        $invoice->load('lines');
        $postingService->post($invoice);

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', 'Invoice posted to ledger');
    }

    public function unpost(BusinessEntity $businessEntity, Invoice $invoice, InvoicePostingService $postingService)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        if (! $invoice->is_posted) {
            return back()->with('info', 'Invoice is not posted.');
        }

        try {
            $postingService->unpost($invoice);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', 'Invoice unposted from ledger.');
    }

    public function recordPayment(
        Request $request,
        BusinessEntity $businessEntity,
        Invoice $invoice,
        InvoicePaymentService $paymentService
    ) {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        $transaction = $paymentService->record($request, $businessEntity, $invoice);
        $invoice->refresh();

        $message = $invoice->status === 'partial'
            ? 'Partial payment recorded against Accounts Receivable.'
            : 'Payment recorded and AR cleared.';

        if ($transaction->payment_channel === Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS) {
            $message = $invoice->status === 'partial'
                ? 'Partial payment recorded via director funds (AR reduced, director loan updated).'
                : 'Payment recorded via director funds (AR cleared against director loan).';
        } elseif ($transaction->bankStatementEntries()->exists()) {
            $message = $invoice->status === 'partial'
                ? 'Partial payment recorded, AR reduced, and matched to the bank statement line.'
                : 'Payment recorded, AR cleared, and matched to the bank statement line.';
        } else {
            $message .= ' Match a statement line later from the bank account panel if needed.';
        }

        return back()->with('success', $message);
    }

    public function remind(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        if ($invoice->status !== 'approved' && $invoice->status !== 'partial') {
            return back()->with('error', 'Reminders can only be sent for approved or partially paid invoices.');
        }

        $invoice->loadMissing(['lease.tenant', 'lines']);
        $tenant = $invoice->lease?->tenant;
        $email = $tenant?->email;
        if (! $email) {
            return back()->with('error', 'No tenant email on file for this invoice.');
        }

        $customerName = $tenant->name ?? $invoice->customer_name;

        try {
            Mail::to($email)->send(new InvoiceReminderMail($invoice, $customerName));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not send email. Check your mail configuration.');
        }

        $invoice->update([
            'last_reminder_sent_at' => now(),
            'reminder_count' => (int) $invoice->reminder_count + 1,
        ]);

        return back()->with('success', 'Reminder sent to '.$email);
    }

    /**
     * @return array{lineAccounts: Collection, defaultAccountCode: string|null, assetsForForm: Collection, tenantsForForm: Collection}
     */
    private function invoiceFormContext(BusinessEntity $businessEntity): array
    {
        $lineAccounts = ChartOfAccount::activeForSelect();
        if ($lineAccounts->isEmpty() || $lineAccounts->firstWhere('account_code', '4100') === null) {
            $this->ensureDefaultRentalIncomeAccount();
            $lineAccounts = ChartOfAccount::activeForSelect();
        }
        $defaultAccountCode = old(
            'lines.0.account_code',
            $lineAccounts->firstWhere('account_code', '4100')?->account_code
                ?? $lineAccounts->first()?->account_code
        );

        // Lease end_date is the planned term/expiry, not "closed". Keep all leases on Active
        // assets in the picker; removing the lease (or inactivating the asset) is what drops it.
        $assets = Asset::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('status', 'Active')
            ->with(['leases' => fn ($query) => $query->with('tenant')->orderByDesc('start_date')])
            ->orderBy('name')
            ->get();

        $assetsForForm = $assets->map(function (Asset $asset) {
            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'leases' => $asset->leases->map(function (Lease $lease) use ($asset) {
                    $tenantName = $lease->tenant?->name ?: 'No tenant';
                    $start = optional($lease->start_date)->format('d/m/Y') ?? '—';
                    $end = $lease->end_date ? $lease->end_date->format('d/m/Y') : 'ongoing';

                    return [
                        'id' => $lease->id,
                        'label' => "{$tenantName} ({$start} – {$end})",
                        'tenant_name' => $lease->tenant?->name,
                        'asset_name' => $asset->name,
                        'gst_applicable' => (bool) ($lease->gst_applicable ?? true),
                    ];
                })->values(),
            ];
        })->values();

        $tenantsForForm = Tenant::query()
            ->whereHas('asset', fn ($q) => $q->where('business_entity_id', $businessEntity->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'asset_id'])
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'asset_id' => $tenant->asset_id,
            ])
            ->values();

        return [
            'lineAccounts' => $lineAccounts,
            'defaultAccountCode' => $defaultAccountCode,
            'assetsForForm' => $assetsForForm,
            'tenantsForForm' => $tenantsForForm,
        ];
    }

    /**
     * @return list<string>
     */
    private function lineAccountCodes(): array
    {
        $codes = ChartOfAccount::activeForSelect()->pluck('account_code')->all();
        if ($codes === [] || ! in_array('4100', $codes, true)) {
            $this->ensureDefaultRentalIncomeAccount();
            $codes = ChartOfAccount::activeForSelect()->pluck('account_code')->all();
        }

        return $codes;
    }

    /**
     * @param  list<string>  $lineAccountCodes
     * @return array<string, mixed>
     */
    private function validateInvoicePayload(
        Request $request,
        BusinessEntity $businessEntity,
        array $lineAccountCodes,
        ?Invoice $invoice = null
    ): array {
        $numberRule = Rule::unique('invoices', 'invoice_number')->where('business_entity_id', $businessEntity->id);
        if ($invoice) {
            $numberRule = $numberRule->ignore($invoice->id);
        }

        $data = $request->validate([
            'invoice_number' => ['required', 'max:50', $numberRule],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($query) => $query->where('business_entity_id', $businessEntity->id)),
            ],
            'lease_id' => ['nullable', 'integer', 'exists:leases,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'gst_basis' => ['required', Rule::in(['inclusive', 'exclusive', 'none'])],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric'],
            'lines.*.account_code' => ['required', 'string', Rule::in($lineAccountCodes)],
            'save_and_post' => ['nullable', 'boolean'],
        ]);

        if ($data['gst_basis'] === 'none') {
            $data['gst_percent'] = 0;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveAssetAndLease(BusinessEntity $businessEntity, array $data): array
    {
        $assetId = ! empty($data['asset_id']) ? (int) $data['asset_id'] : null;
        $leaseId = ! empty($data['lease_id']) ? (int) $data['lease_id'] : null;

        if ($leaseId) {
            $lease = Lease::query()->with(['asset', 'tenant'])->findOrFail($leaseId);
            if ((int) $lease->asset->business_entity_id !== (int) $businessEntity->id) {
                throw ValidationException::withMessages([
                    'lease_id' => 'The selected lease does not belong to this entity.',
                ]);
            }
            if ($assetId !== null && (int) $lease->asset_id !== $assetId) {
                throw ValidationException::withMessages([
                    'lease_id' => 'The selected lease does not belong to the selected asset.',
                ]);
            }
            $assetId = (int) $lease->asset_id;
        }

        return [$assetId, $leaseId];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncInvoiceLines(Invoice $invoice, array $lines, float $gstRate, string $gstBasis): void
    {
        $subtotal = 0.0;
        $gstTotal = 0.0;
        $grand = 0.0;

        foreach ($lines as $line) {
            $amounts = $this->calculateInvoiceLineAmounts(
                (float) $line['quantity'],
                (float) $line['unit_price'],
                $gstRate,
                $gstBasis
            );

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $amounts['line_total'],
                'gst_rate' => $gstRate,
                'account_code' => $line['account_code'],
            ]);

            $subtotal += $amounts['net'];
            $gstTotal += $amounts['gst'];
            $grand += $amounts['line_total'];
        }

        $invoice->subtotal = round($subtotal, 2);
        $invoice->gst_amount = round($gstTotal, 2);
        $invoice->total_amount = round($grand, 2);
        $invoice->save();
    }

    private function ensureDefaultRentalIncomeAccount(): ChartOfAccount
    {
        $account = ChartOfAccount::firstOrCreate(
            ['account_code' => '4100'],
            [
                'account_name' => 'Rental Income',
                'account_type' => 'income',
                'account_category' => 'operating_income',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]
        );

        if (! $account->is_active
            || $account->account_type !== 'income'
            || $account->account_name !== 'Rental Income') {
            $account->fill([
                'account_name' => 'Rental Income',
                'account_type' => 'income',
                'account_category' => 'operating_income',
                'is_active' => true,
            ]);
            $account->save();
        }

        return $account->refresh();
    }

    /**
     * @return array{net: float, gst: float, line_total: float}
     */
    private function calculateInvoiceLineAmounts(float $quantity, float $unitPrice, float $gstRate, string $gstBasis): array
    {
        if ($gstBasis === 'none' || $gstRate <= 0) {
            $lineTotal = round($quantity * $unitPrice, 2);

            return [
                'net' => $lineTotal,
                'gst' => 0.0,
                'line_total' => $lineTotal,
            ];
        }

        if ($gstBasis === 'inclusive') {
            $lineTotal = round($quantity * $unitPrice, 2);
            $net = round($lineTotal / (1 + $gstRate), 2);
            $gst = round($lineTotal - $net, 2);

            return [
                'net' => $net,
                'gst' => $gst,
                'line_total' => $lineTotal,
            ];
        }

        $net = round($quantity * $unitPrice, 2);
        $gst = round($net * $gstRate, 2);
        $lineTotal = round($net + $gst, 2);

        return [
            'net' => $net,
            'gst' => $gst,
            'line_total' => $lineTotal,
        ];
    }

    private function applyInvoiceListFilters(
        $query,
        string $statusFilter,
        bool $receivableOnly,
        ?int $assetIdFilter,
        ?int $leaseIdFilter
    ): void {
        if ($receivableOnly) {
            $query->where('status', 'approved')->whereNull('paid_at');
        } elseif ($statusFilter !== '' && array_key_exists($statusFilter, Invoice::$statuses)) {
            $query->where('status', $statusFilter);
        }

        if ($assetIdFilter) {
            $query->where('asset_id', $assetIdFilter);
        }

        if ($leaseIdFilter) {
            $query->where('lease_id', $leaseIdFilter);
        }
    }

    private function authorizeInvoice(BusinessEntity $businessEntity, Invoice $invoice): void
    {
        abort_unless((int) $invoice->business_entity_id === (int) $businessEntity->id, 404);
        $this->ensureOperationalForAccounting($businessEntity);
    }
}
