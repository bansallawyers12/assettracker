<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Support\TransactionPayerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BalanceSheetEntryController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function create(Request $request, BusinessEntity $businessEntity): View
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $transactionData = [
            'date' => now()->toDateString(),
            'amount' => '',
            'description' => '',
            'transaction_type' => 'asset_purchase',
            'asset_id' => '',
            'payment_status' => 'paid',
            'payment_channel' => Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
            'paid_by' => '',
        ];

        if ($request->filled('payment_channel')) {
            $requestedChannel = (string) $request->input('payment_channel');
            if (array_key_exists($requestedChannel, Transaction::nonBankPaymentChannels())) {
                $transactionData['payment_channel'] = $requestedChannel;
            }
        }

        if ($request->filled('transaction_type')) {
            $requestedType = (string) $request->input('transaction_type');
            if (array_key_exists($requestedType, Transaction::balanceSheetTypes())) {
                $transactionData['transaction_type'] = $requestedType;
            }
        }

        $payerOptions = TransactionPayerResolver::payerOptions();
        $returnContext = $this->returnContextFromRequest($request);

        return view('business-entities.balance-sheet-entries.create', compact(
            'businessEntity',
            'transactionData',
            'payerOptions',
            'returnContext'
        ));
    }

    public function store(Request $request, BusinessEntity $businessEntity): RedirectResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $this->normalizeOptionalIds($request);

        $balanceSheetTypes = array_keys(Transaction::balanceSheetTypes());
        $nonBankChannels = array_keys(Transaction::nonBankPaymentChannels());

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:'.implode(',', $balanceSheetTypes),
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $businessEntity->id)),
            ],
            'payment_channel' => 'required|in:'.implode(',', $nonBankChannels),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'return_to' => 'nullable|in:bank-account,entity,transactions-page',
            'return_business_entity_id' => 'nullable|integer',
            'return_bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
        ]);

        $request->merge([
            'payment_status' => 'paid',
        ]);

        $paidBy = $this->validatedPaidBy($request);
        $paymentChannel = (string) $request->input('payment_channel');

        $transaction = DB::transaction(function () use (
            $request,
            $businessEntity,
            $paidBy,
            $paymentChannel
        ) {
            return Transaction::create([
                'business_entity_id' => $businessEntity->id,
                'asset_id' => $request->filled('asset_id') ? $request->integer('asset_id') : null,
                'bank_account_id' => null,
                'date' => $request->date,
                'amount' => $request->amount,
                'description' => $request->description,
                'vendor_id' => null,
                'vendor_name' => null,
                'invoice_number' => null,
                'transaction_type' => $request->transaction_type,
                'gst_amount' => null,
                'gst_status' => 'gst_free',
                'gst_basis' => null,
                'receipt_path' => null,
                'document_id' => null,
                'payment_status' => 'paid',
                'due_date' => null,
                'paid_at' => $request->date,
                'payment_method' => null,
                'payment_channel' => $paymentChannel,
                'paid_by' => $paidBy,
                'payment_document_id' => null,
                'subject_to_bas' => false,
                'is_flagged' => false,
                'comments' => null,
            ]);
        });

        $success = "Balance sheet entry '".($transaction->description ?: 'saved')."' added successfully!";

        return $this->redirectAfterStore($request, $businessEntity, $success);
    }

    /**
     * @return array{return_to: ?string, return_business_entity_id: ?int, return_bank_account_id: ?int}
     */
    private function returnContextFromRequest(Request $request): array
    {
        $returnTo = $request->input('return_to');
        if (! in_array($returnTo, ['bank-account', 'entity', 'transactions-page'], true)) {
            $returnTo = null;
        }

        return [
            'return_to' => $returnTo,
            'return_business_entity_id' => $request->filled('return_business_entity_id')
                ? $request->integer('return_business_entity_id')
                : null,
            'return_bank_account_id' => $request->filled('return_bank_account_id')
                ? $request->integer('return_bank_account_id')
                : null,
        ];
    }

    private function redirectAfterStore(
        Request $request,
        BusinessEntity $businessEntity,
        string $success
    ): RedirectResponse {
        $returnTo = $request->input('return_to');
        $bankAccountId = $request->filled('return_bank_account_id')
            ? $request->integer('return_bank_account_id')
            : null;

        if ($returnTo === 'bank-account' && $bankAccountId) {
            return redirect()
                ->route('bank-accounts.index')
                ->with('success', $success)
                ->with('open_bank_transactions_account_id', $bankAccountId);
        }

        if ($returnTo === 'transactions-page' && $bankAccountId) {
            $account = BankAccount::query()->find($bankAccountId);
            if ($account) {
                return redirect()
                    ->route('bank-accounts.transactions.page', array_filter([
                        'bankAccount' => $account,
                        'business_entity_id' => $request->filled('return_business_entity_id')
                            ? $request->integer('return_business_entity_id')
                            : $businessEntity->id,
                    ]))
                    ->with('success', $success);
            }
        }

        if ($returnTo === 'entity' && $bankAccountId) {
            return redirect()
                ->route('business-entities.show', [
                    'business_entity' => $businessEntity->id,
                    'open_bank_transactions' => $bankAccountId,
                ])
                ->withFragment('tab_bank_accounts')
                ->with('success', $success);
        }

        return redirect()
            ->route('business-entities.show', $businessEntity)
            ->with('success', $success);
    }

    private function normalizeOptionalIds(Request $request): void
    {
        foreach (['asset_id', 'return_bank_account_id', 'return_business_entity_id'] as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }
    }

    private function validatedPaidBy(Request $request): ?string
    {
        $raw = $request->input('paid_by_select');
        if (is_array($raw)) {
            throw ValidationException::withMessages([
                'paid_by_select' => 'Invalid payer selection.',
            ]);
        }
        $sel = trim((string) ($raw ?? ''));
        if ($sel !== '' && $sel !== 'other' && ! preg_match('/^(be|ep):\d+$/', $sel)) {
            throw ValidationException::withMessages([
                'paid_by_select' => 'Invalid payer selection.',
            ]);
        }

        $paidBy = TransactionPayerResolver::resolveFromRequest($request);
        $transactionType = trim((string) $request->input('transaction_type', ''));

        if ($transactionType !== '') {
            $direction = Transaction::directionFromType($transactionType);
            if ($paidBy === null || trim($paidBy) === '') {
                throw ValidationException::withMessages([
                    'paid_by_select' => $direction === 'income'
                        ? 'Received by is required.'
                        : 'Paid by is required.',
                ]);
            }
        }

        TransactionPayerResolver::assertSelectionAllowed($paidBy);

        return $paidBy;
    }
}
