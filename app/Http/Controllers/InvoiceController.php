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
use App\Services\BankStatementMatchSuggester;
use App\Services\InvoicePaymentService;
use App\Services\InvoicePostingService;
use App\Support\TableSort;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        if ($businessEntity) {
            $this->authorize('view', $businessEntity);
            $this->ensureOperationalForAccounting($businessEntity);

            $query = Invoice::where('business_entity_id', $businessEntity->id)->with(['asset']);
            $tableSort->applyToQuery($query, [
                'number' => 'invoice_number',
                'customer' => 'customer_name',
                'issue' => 'issue_date',
                'total' => 'total_amount',
                'status' => 'status',
            ], 'issue');

            $invoices = $query->paginate(20)->withQueryString();

            return view('invoices.index', compact('businessEntity', 'invoices', 'tableSort'));
        }

        $query = Invoice::query()
            ->whereIn('business_entity_id', BusinessEntity::query()->operationalEntities()->pluck('id'))
            ->with(['asset', 'businessEntity']);

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

        return view('invoices.index', compact('invoices', 'tableSort'));
    }

    public function create(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $issueDate = old('issue_date', now()->toDateString());
        $suggestedInvoiceNumber = old('invoice_number', Invoice::suggestNumber($businessEntity, $issueDate));
        $defaultDueDate = old('due_date', Carbon::parse($issueDate)->addDays(30)->toDateString());

        $incomeAccounts = ChartOfAccount::activeIncomeForSelect();
        $defaultAccountCode = old(
            'lines.0.account_code',
            $incomeAccounts->firstWhere('account_code', '4100')?->account_code
                ?? $incomeAccounts->first()?->account_code
        );

        $assets = Asset::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('status', 'Active')
            ->with([
                'leases' => fn ($query) => $query->with('tenant')->orderByDesc('start_date'),
            ])
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
                    ];
                })->values(),
            ];
        })->values();

        return view('invoices.create', [
            'businessEntity' => $businessEntity,
            'suggestedInvoiceNumber' => $suggestedInvoiceNumber,
            'defaultDueDate' => $defaultDueDate,
            'issueDate' => $issueDate,
            'incomeAccounts' => $incomeAccounts,
            'defaultAccountCode' => $defaultAccountCode,
            'assetsForForm' => $assetsForForm,
        ]);
    }

    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $incomeAccountCodes = ChartOfAccount::activeIncomeForSelect()->pluck('account_code')->all();

        $data = $request->validate([
            'invoice_number' => [
                'required',
                'max:50',
                Rule::unique('invoices', 'invoice_number')->where('business_entity_id', $businessEntity->id),
            ],
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
            'gst_basis' => ['required', Rule::in(['inclusive', 'exclusive'])],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.account_code' => ['required', 'string', Rule::in($incomeAccountCodes)],
        ]);

        $assetId = isset($data['asset_id']) ? (int) $data['asset_id'] : null;
        $leaseId = isset($data['lease_id']) ? (int) $data['lease_id'] : null;
        $lease = null;

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

        $gstRate = round(((float) $data['gst_percent']) / 100, 4);
        $gstBasis = $data['gst_basis'];

        $invoice = new Invoice;
        $invoice->fill([
            'invoice_number' => $data['invoice_number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? Carbon::parse($data['issue_date'])->addDays(30)->toDateString(),
            'customer_name' => $data['customer_name'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'currency' => $data['currency'] ?? 'AUD',
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

        $subtotal = 0.0;
        $gstTotal = 0.0;
        $grand = 0.0;

        foreach ($data['lines'] as $line) {
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

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', 'Invoice created');
    }

    /**
     * @return array{net: float, gst: float, line_total: float}
     */
    private function calculateInvoiceLineAmounts(float $quantity, float $unitPrice, float $gstRate, string $gstBasis): array
    {
        if ($gstRate <= 0) {
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

    public function show(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('view', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        $invoice->load(['lines', 'lease.tenant', 'asset', 'paymentTransaction.bankAccount', 'paymentTransaction.bankStatementEntries']);

        $paymentBankAccounts = collect();
        $unmatchedStatementEntries = collect();
        $suggestedStatementEntryId = null;
        $suggestedPaymentBankAccountId = null;
        if ($invoice->status === 'approved' && ! $invoice->paid_at) {
            $paymentBankAccounts = $businessEntity->bankAccountLinksForDisplay()
                ->map(fn ($link) => $link->bankAccount)
                ->filter()
                ->unique('id')
                ->filter(fn (BankAccount $account) => $account->canUseForTransaction($businessEntity))
                ->sortBy('account_name')
                ->values();

            if ($paymentBankAccounts->isNotEmpty()) {
                $invoiceTotal = round((float) $invoice->total_amount, 2);
                $unmatchedStatementEntries = BankStatementEntry::query()
                    ->whereIn('bank_account_id', $paymentBankAccounts->pluck('id'))
                    ->whereNull('transaction_id')
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get()
                    ->sortByDesc(function (BankStatementEntry $entry) use ($invoiceTotal) {
                        $amount = (float) $entry->amount;
                        if ($amount <= 0) {
                            return -1;
                        }

                        return abs($amount - $invoiceTotal) <= BankStatementMatchSuggester::AMOUNT_TOLERANCE
                            ? 2
                            : 0;
                    })
                    ->values();

                $suggestedEntry = $unmatchedStatementEntries
                    ->first(function (BankStatementEntry $entry) use ($invoiceTotal) {
                        $amount = (float) $entry->amount;

                        return $amount > 0
                            && abs($amount - $invoiceTotal) <= BankStatementMatchSuggester::AMOUNT_TOLERANCE;
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
            'suggestedPaymentBankAccountId'
        ));
    }

    public function edit(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        $invoice->load('lines');

        return view('invoices.edit', compact('businessEntity', 'invoice'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);
        if ($invoice->is_posted) {
            return back()->with('error', 'Posted invoices cannot be edited.');
        }

        $data = $request->validate([
            'invoice_number' => [
                'required',
                'max:50',
                Rule::unique('invoices', 'invoice_number')->where('business_entity_id', $businessEntity->id)->ignore($invoice->id),
            ],
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'customer_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.gst_rate' => 'nullable|numeric|min:0',
            'lines.*.account_code' => 'nullable|string|max:20',
        ]);

        $invoice->fill($data);
        $invoice->currency = $data['currency'] ?? $invoice->currency;
        $invoice->save();

        $invoice->lines()->delete();
        $subtotal = 0;
        $gstTotal = 0;
        $grand = 0;
        foreach ($data['lines'] as $line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $gstRate = isset($line['gst_rate']) ? (float) $line['gst_rate'] : 0.1;
            $lineTotal = round($qty * $price * (1 + $gstRate), 2);
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'description' => $line['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'gst_rate' => $gstRate,
                'account_code' => $line['account_code'] ?? null,
            ]);
            $net = round($qty * $price, 2);
            $gst = round($net * $gstRate, 2);
            $subtotal += $net;
            $gstTotal += $gst;
            $grand += $net + $gst;
        }
        $invoice->subtotal = $subtotal;
        $invoice->gst_amount = $gstTotal;
        $invoice->total_amount = $grand;
        $invoice->save();

        return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
            ->with('success', 'Invoice updated');
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

        $message = 'Payment recorded and AR cleared.';
        if ($transaction->bankStatementEntries()->exists()) {
            $message = 'Payment recorded, AR cleared, and matched to the bank statement line.';
        } else {
            $message .= ' Match a statement line later from the bank account panel if needed.';
        }

        return back()->with('success', $message);
    }

    public function remind(BusinessEntity $businessEntity, Invoice $invoice)
    {
        $this->authorize('update', $businessEntity);
        $this->authorizeInvoice($businessEntity, $invoice);

        if ($invoice->status !== 'approved') {
            return back()->with('error', 'Reminders can only be sent for approved (posted) invoices.');
        }

        $invoice->loadMissing(['lease.tenant']);
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

    private function authorizeInvoice(BusinessEntity $businessEntity, Invoice $invoice): void
    {
        abort_unless((int) $invoice->business_entity_id === (int) $businessEntity->id, 404);
        $this->ensureOperationalForAccounting($businessEntity);
    }
}
