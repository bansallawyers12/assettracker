<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Services\DocumentUploadService;
use App\Support\TransactionGstResolver;
use App\Support\TransactionPayerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BalanceSheetEntryController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(private DocumentUploadService $documentUploadService) {}

    public function create(Request $request, BusinessEntity $businessEntity): View
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $transactionData = [
            'date' => now()->toDateString(),
            'amount' => '',
            'description' => '',
            'vendor_id' => '',
            'invoice_number' => '',
            'transaction_type' => 'asset_purchase',
            'gst_amount' => '',
            'gst_basis' => '',
            'receipt_path' => '',
            'asset_id' => '',
            'payment_status' => 'paid',
            'paid_at' => now()->toDateString(),
            'payment_method' => '',
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
        $vendors = Vendor::orderedForSelect();
        $returnContext = $this->returnContextFromRequest($request);

        return view('business-entities.balance-sheet-entries.create', compact(
            'businessEntity',
            'transactionData',
            'payerOptions',
            'vendors',
            'returnContext'
        ));
    }

    public function store(Request $request, BusinessEntity $businessEntity): RedirectResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $this->normalizeOptionalIds($request);
        $this->prepareUploadValidation($request);

        $balanceSheetTypes = array_keys(Transaction::balanceSheetTypes());
        $nonBankChannels = array_keys(Transaction::nonBankPaymentChannels());

        $request->validate(array_merge([
            'date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'invoice_number' => 'nullable|string|max:100',
            'transaction_type' => 'required|in:'.implode(',', $balanceSheetTypes),
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $businessEntity->id)),
            ],
            'gst_amount' => 'nullable|numeric',
            'gst_basis' => 'nullable|in:inclusive,exclusive',
            'document_name' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|in:'.implode(',', array_keys(Transaction::$paymentMethods)),
            'payment_channel' => 'required|in:'.implode(',', $nonBankChannels),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'payment_document_name' => 'nullable|string|max:255',
            'subject_to_bas' => ['sometimes', 'boolean'],
            'is_flagged' => ['sometimes', 'boolean'],
            'comments' => ['nullable', 'string'],
            'return_to' => 'nullable|in:bank-account,entity,transactions-page',
            'return_business_entity_id' => 'nullable|integer',
            'return_bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
        ], $this->receiptUploadRules()), $this->receiptValidationMessages());

        $this->validateGstBasis($request);

        $request->merge([
            'payment_status' => 'paid',
        ]);

        $gstResolved = TransactionGstResolver::resolve(
            (float) $request->amount,
            $request->input('gst_basis') ?: null,
            $request->input('gst_amount'),
            Transaction::directionFromType((string) $request->transaction_type)
        );

        $asset = $request->filled('asset_id')
            ? Asset::query()->find($request->integer('asset_id'))
            : null;

        $paidBy = $this->validatedPaidBy($request);
        $vendorData = $this->resolveVendorData($request);
        $paymentChannel = (string) $request->input('payment_channel');

        $transaction = DB::transaction(function () use (
            $request,
            $businessEntity,
            $asset,
            $gstResolved,
            $paidBy,
            $vendorData,
            $paymentChannel
        ) {
            $receiptPath = null;
            $documentId = null;
            $prefillPath = $request->input('receipt_path');

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $displayName = $this->buildReceiptUploadDisplayName($request, $file);
                $labelBase = $request->filled('document_name')
                    ? trim((string) $request->input('document_name'))
                    : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $desc = trim('Balance sheet entry receipt'.($request->description ? ': '.$request->description : ''));
                $document = $this->documentUploadService->createTransactionReceiptDocumentFromUpload(
                    $businessEntity,
                    $asset,
                    $file,
                    $displayName,
                    $labelBase ?: 'Receipt',
                    $desc !== '' ? $desc : null
                );
                $receiptPath = $document->path;
                $documentId = $document->id;
            } elseif (
                is_string($prefillPath)
                && $this->prefillReceiptPathAllowedForEntity($prefillPath, $businessEntity)
                && Storage::disk('s3')->exists($prefillPath)
            ) {
                $displayName = basename(str_replace('\\', '/', $prefillPath));
                $labelBase = pathinfo($displayName, PATHINFO_FILENAME) ?: 'Receipt';
                $desc = trim('Balance sheet entry receipt'.($request->description ? ': '.$request->description : ''));
                $document = $this->documentUploadService->createTransactionReceiptFromExistingS3Path(
                    $businessEntity,
                    $asset,
                    $prefillPath,
                    $displayName,
                    $labelBase,
                    $desc !== '' ? $desc : null
                );
                $receiptPath = $document->path;
                $documentId = $document->id;
            }

            $paymentDocumentId = null;
            if ($request->hasFile('payment_document')) {
                $payFile = $request->file('payment_document');
                $payDisplayName = $this->buildReceiptUploadDisplayName($request, $payFile, 'payment_document_name');
                $payLabelBase = $request->filled('payment_document_name')
                    ? trim((string) $request->input('payment_document_name'))
                    : pathinfo($payFile->getClientOriginalName(), PATHINFO_FILENAME);
                $payDesc = trim('Payment receipt'.($request->description ? ': '.$request->description : ''));
                $payDocument = $this->documentUploadService->createTransactionReceiptDocumentFromUpload(
                    $businessEntity,
                    $asset,
                    $payFile,
                    $payDisplayName,
                    $payLabelBase ?: 'Payment Receipt',
                    $payDesc !== '' ? $payDesc : null
                );
                $paymentDocumentId = $payDocument->id;
            }

            return Transaction::create([
                'business_entity_id' => $businessEntity->id,
                'asset_id' => $request->filled('asset_id') ? $request->integer('asset_id') : null,
                'bank_account_id' => null,
                'date' => $request->date,
                'amount' => $request->amount,
                'description' => $request->description,
                'vendor_id' => $vendorData['vendor_id'],
                'vendor_name' => $vendorData['vendor_name'],
                'invoice_number' => $request->invoice_number,
                'transaction_type' => $request->transaction_type,
                'gst_amount' => $gstResolved['gst_amount'],
                'gst_status' => $gstResolved['gst_status'],
                'gst_basis' => $gstResolved['gst_basis'],
                'receipt_path' => $receiptPath,
                'document_id' => $documentId,
                'payment_status' => 'paid',
                'due_date' => null,
                'paid_at' => $request->paid_at ?: $request->date,
                'payment_method' => $request->payment_method,
                'payment_channel' => $paymentChannel,
                'paid_by' => $paidBy,
                'payment_document_id' => $paymentDocumentId,
                'subject_to_bas' => $request->boolean('subject_to_bas'),
                'is_flagged' => $request->boolean('is_flagged'),
                'comments' => $request->input('comments'),
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
        foreach (['asset_id', 'vendor_id', 'return_bank_account_id', 'return_business_entity_id'] as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }
    }

    private function prepareUploadValidation(Request $request): void
    {
        // Mirror BusinessEntityController: empty optional file fields should not fail mimes.
        foreach (['document', 'payment_document'] as $field) {
            if ($request->hasFile($field) && ! $request->file($field)->isValid() && $request->file($field)->getError() === UPLOAD_ERR_NO_FILE) {
                $request->files->remove($field);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function receiptUploadRules(): array
    {
        $maxKb = max(1, (int) config('documents.max_kilobytes', 20480));
        $mimes = (string) config('documents.mimes', 'pdf,jpeg,png,jpg');
        $rule = "nullable|file|mimes:{$mimes}|max:{$maxKb}";

        return [
            'document' => $rule,
            'payment_document' => $rule,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function receiptValidationMessages(): array
    {
        $maxKb = max(1, (int) config('documents.max_kilobytes', 20480));
        $maxMb = number_format($maxKb / 1024, 1);

        return [
            'document.max' => "Invoice / bill is larger than the app limit ({$maxKb} KB, ~{$maxMb} MB).",
            'payment_document.max' => "Payment receipt is larger than the app limit ({$maxKb} KB, ~{$maxMb} MB).",
        ];
    }

    private function validateGstBasis(Request $request): void
    {
        $raw = $request->input('gst_amount');
        if ($raw === null || $raw === '') {
            return;
        }
        if (! is_numeric($raw) || round((float) $raw, 2) <= 0) {
            return;
        }
        if (! in_array($request->input('gst_basis'), ['inclusive', 'exclusive'], true)) {
            throw ValidationException::withMessages([
                'gst_basis' => 'Select whether the amount is GST inclusive or GST exclusive when you enter a GST amount.',
            ]);
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

    /**
     * @return array{vendor_id: ?int, vendor_name: ?string}
     */
    private function resolveVendorData(Request $request): array
    {
        $vendorId = $request->filled('vendor_id') ? (int) $request->input('vendor_id') : null;
        $vendorName = null;

        if ($vendorId) {
            $vendorName = Vendor::query()->find($vendorId)?->name;
        }

        return [
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
        ];
    }

    private function prefillReceiptPathAllowedForEntity(?string $path, BusinessEntity $entity): bool
    {
        if ($path === null || $path === '') {
            return false;
        }
        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..')) {
            return false;
        }

        $needle = 'Receipts/'.$entity->id.'_';

        return str_starts_with($path, $needle)
            || str_starts_with($path, 'BusinessEntities/'.$entity->id.'_');
    }

    private function buildReceiptUploadDisplayName(Request $request, UploadedFile $file, string $nameField = 'document_name'): string
    {
        if (! $request->filled($nameField)) {
            return $file->getClientOriginalName();
        }

        $base = trim((string) $request->input($nameField, ''));
        if ($base === '') {
            return $file->getClientOriginalName();
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            return $base;
        }

        $lowerBase = strtolower($base);
        if (str_ends_with($lowerBase, '.'.$ext)) {
            return $base;
        }

        return $base.'.'.$ext;
    }
}
