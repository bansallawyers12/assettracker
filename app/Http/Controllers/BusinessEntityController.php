<?php

namespace App\Http\Controllers;

// Import necessary models and classes
use App\Mail\ContactEmail;
use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\BusinessEntityBankAccount;
use App\Models\Document;
use App\Models\EmailTemplate;
use App\Models\EntityPerson;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Person; // Added for date manipulation
use App\Models\Reminder; // Added for logging
use App\Models\Transaction; // Added for file storage
use App\Models\Vendor;
use App\Rules\UniqueAbnHash;
use App\Rules\UniqueAcnHash;
use App\Services\BankAccountAssetLinkService;
use App\Services\CommitmentReportService;
use App\Services\DocumentUploadService;
use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Support\SecurityAuditLogger;
use App\Support\TransactionGstResolver;
use App\Support\TransactionPayerResolver;
use Carbon\Carbon; // Added for handling validation exceptions
use Illuminate\Database\QueryException;
// Add this at the top with other use statements

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BusinessEntityController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(
        private DocumentUploadService $documentUploadService,
        private BankAccountAssetLinkService $bankAccountAssetLinkService
    ) {}

    /**
     * Validation rules for transaction invoice / payment uploads (KB max matches config/documents.php).
     *
     * @return array<string, string>
     */
    private function transactionReceiptUploadRules(bool $includeInvoiceField = true): array
    {
        $maxKb = max(1, (int) config('documents.max_kilobytes', 10240));
        $mimes = (string) config('documents.mimes', 'pdf,jpeg,png,jpg');
        $rule = "nullable|file|mimes:{$mimes}|max:{$maxKb}";
        $out = ['payment_document' => $rule];
        if ($includeInvoiceField) {
            $out['document'] = $rule;
        }

        return $out;
    }

    /**
     * Human-readable validation messages for transaction file fields (explicit limits + PHP ini hints).
     *
     * @return array<string, string>
     */
    private function transactionReceiptValidationMessages(): array
    {
        $maxKb = max(1, (int) config('documents.max_kilobytes', 10240));
        $maxMb = number_format($maxKb / 1024, 1);
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');

        return [
            'document.max' => "Invoice / bill is larger than the app limit ({$maxKb} KB, ~{$maxMb} MB from DOCUMENTS_MAX_KB / config documents.max_kilobytes). PHP upload_max_filesize is {$uploadMax} and post_max_size is {$postMax} — both must be at least as large as your file. Restart Apache/nginx after changing php.ini.",
            'payment_document.max' => "Payment receipt is larger than the app limit ({$maxKb} KB, ~{$maxMb} MB from DOCUMENTS_MAX_KB / config documents.max_kilobytes). PHP upload_max_filesize is {$uploadMax} and post_max_size is {$postMax} — both must be at least as large as your file. Restart Apache/nginx after changing php.ini.",
            'document.mimes' => 'Invoice / bill type is not allowed, or PHP could not detect the type. Allowed extensions match config documents.mimes (try PDF or a common image format).',
            'payment_document.mimes' => 'Payment receipt type is not allowed, or PHP could not detect the type. Allowed extensions match config documents.mimes (try PDF or a common image format).',
            'document.uploaded' => 'Invoice / bill did not upload successfully. See the detailed message on this field or check PHP upload_max_filesize / post_max_size.',
            'payment_document.uploaded' => 'Payment receipt did not upload successfully. See the detailed message on this field or check PHP upload_max_filesize / post_max_size.',
        ];
    }

    private static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $unit = strtolower($value[strlen($value) - 1]);
        if (in_array($unit, ['g', 'm', 'k'], true)) {
            $num = (float) substr($value, 0, -1);
        } else {
            $num = (float) $value;
            $unit = 'b';
        }

        return (int) match ($unit) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * When the raw body is larger than post_max_size, PHP discards POST data — explain before generic "required" errors.
     */
    private function rejectOversizedMultipartRequest(Request $request): void
    {
        $ct = (string) $request->header('Content-Type', '');
        if (! str_contains($ct, 'multipart/form-data')) {
            return;
        }
        $len = (int) $request->header('Content-Length', 0);
        if ($len <= 0) {
            return;
        }
        $postMaxBytes = self::iniSizeToBytes((string) ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $len > $postMaxBytes) {
            throw ValidationException::withMessages([
                'document' => 'This request is about '.self::formatBytes($len).' but PHP post_max_size is only '.ini_get('post_max_size').'. Increase post_max_size in php.ini (it must be larger than your largest upload; usually ≥ upload_max_filesize). Restart the web server.',
            ]);
        }
    }

    /**
     * If PHP dropped a large multipart body, $_POST / $_FILES are empty but Content-Length can still be set.
     */
    private function hintIfMultipartBodyLikelyDiscarded(Request $request): void
    {
        $ct = (string) $request->header('Content-Type', '');
        if (! str_contains($ct, 'multipart/form-data')) {
            return;
        }
        $len = (int) $request->header('Content-Length', 0);
        if ($len < 512 * 1024) {
            return;
        }
        if ($request->request->count() > 0 || $request->files->count() > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'document' => 'No form data was received, but the browser reported a large upload (~'.self::formatBytes($len).'). This usually means the request exceeded PHP post_max_size ('.ini_get('post_max_size').'). Increase post_max_size and upload_max_filesize in php.ini and restart the web server.',
        ]);
    }

    private function assertPhpUploadSucceeded(Request $request, string $field, string $label): void
    {
        if (! $request->hasFile($field)) {
            return;
        }
        $file = $request->file($field);
        if ($file->isValid()) {
            return;
        }

        $iniUp = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        $code = $file->getError();
        $msg = match ($code) {
            UPLOAD_ERR_INI_SIZE => "The {$label} is larger than PHP upload_max_filesize (currently {$iniUp}). Increase upload_max_filesize in php.ini; post_max_size (currently {$postMax}) should be greater than or equal to that value. Restart the web server.",
            UPLOAD_ERR_FORM_SIZE => "The {$label} exceeded the HTML MAX_FILE_SIZE limit for this form.",
            UPLOAD_ERR_PARTIAL => "The {$label} was only partially uploaded. Try again with a stable connection.",
            UPLOAD_ERR_NO_FILE => "No file was received for the {$label}.",
            UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder for uploads (see upload_tmp_dir in php.ini).',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked this file upload.',
            default => "The {$label} upload failed (PHP upload error code {$code}). Check upload_max_filesize ({$iniUp}) and post_max_size ({$postMax}).",
        };

        throw ValidationException::withMessages([$field => $msg]);
    }

    /**
     * @param  list<string>  $fileFields  e.g. ['document', 'payment_document']
     */
    private function prepareTransactionUploadValidation(Request $request, array $fileFields): void
    {
        $this->rejectOversizedMultipartRequest($request);
        $this->hintIfMultipartBodyLikelyDiscarded($request);
        foreach ($fileFields as $field) {
            $label = $field === 'payment_document' ? 'payment receipt' : 'invoice / bill file';
            $this->assertPhpUploadSucceeded($request, $field, $label);
        }
    }

    /**
     * Display a listing of the business entities.
     *
     * @return View
     */
    public function index()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::query()
            ->operationalEntities()
            ->with('persons')
            ->orderBy('legal_name')
            ->get();

        $tenancyContactEntities = BusinessEntity::query()
            ->where('exclude_from_financial_reports', true)
            ->orderBy('legal_name')
            ->get();

        return view('business-entities.index', compact('businessEntities', 'tenancyContactEntities'));
    }

    /**
     * Show the form for creating a new business entity.
     *
     * @return View
     */
    public function create()
    {
        $this->authorize('create', BusinessEntity::class);

        $persons = Person::query()
            ->orderBy('id')
            ->get();

        $businessEntities = BusinessEntity::query()
            ->operationalEntities()
            ->where('entity_type', '!=', 'Trust')
            ->orderBy('legal_name')
            ->get();

        return view('business-entities.create', compact('persons', 'businessEntities'));
    }

    /**
     * Store a newly created business entity in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', BusinessEntity::class);

        // Validate the incoming request data
        $request->validate([
            'legal_name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'abn' => ['nullable', 'string', 'max:11', new UniqueAbnHash()],
            'acn' => ['nullable', 'string', 'max:9', new UniqueAcnHash()],
            'tfn' => 'nullable|string|max:9',
            'corporate_key' => 'nullable|string|max:255',
            'registered_address' => 'required|string',
            'registered_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:15',
            'asic_renewal_date' => 'nullable|date',
            'bas_reporting_frequency' => 'nullable|in:annual,quarterly,monthly',
            'uses_tax_agent' => 'nullable|boolean',
            'gst_registered' => 'nullable|boolean',
            'entity_tax_return_required' => 'nullable|boolean',

            // Trust-specific validation (nullable first so empty values skip date/rules when entity is not Trust)
            'trust_type' => 'nullable|required_if:entity_type,Trust|in:Discretionary,Unit,Fixed,Testamentary,Charitable',
            'trust_establishment_date' => 'nullable|required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_date' => 'nullable|required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_reference' => 'nullable|string|max:255',
            'trust_vesting_date' => 'nullable|date|after:trust_establishment_date',
            'trust_vesting_conditions' => 'nullable|string|max:1000',
            'appointor_type' => 'nullable|required_if:entity_type,Trust|in:person,entity',
            'appointor_person_id' => [
                'nullable',
                'required_if:appointor_type,person',
                Rule::exists('persons', 'id'),
            ],
            'appointor_entity_id' => [
                'nullable',
                'required_if:appointor_type,entity',
                BusinessEntity::ruleExistsOperationalAppointorCompany(),
            ],
            'exclude_from_financial_reports' => 'nullable|boolean',
        ], [
            'trust_type.required_if' => 'Trust type is required when entity type is Trust.',
            'trust_establishment_date.required_if' => 'Trust establishment date is required when entity type is Trust.',
            'trust_deed_date.required_if' => 'Trust deed date is required when entity type is Trust.',
            'appointor_type.required_if' => 'Appointor type is required when entity type is Trust.',
            'appointor_person_id.required_if' => 'Please select an appointor person.',
            'appointor_entity_id.required_if' => 'Please select an appointor entity.',
        ]);

        try {
            BusinessEntity::create([
                'legal_name' => $request->legal_name,
                'trading_name' => $request->trading_name,
                'entity_type' => $request->entity_type,
                'trust_type' => $request->trust_type,
                'trust_establishment_date' => $request->trust_establishment_date,
                'trust_deed_date' => $request->trust_deed_date,
                'trust_deed_reference' => $request->trust_deed_reference,
                'trust_vesting_date' => $request->trust_vesting_date,
                'trust_vesting_conditions' => $request->trust_vesting_conditions,
                'appointor_person_id' => $request->appointor_person_id,
                'appointor_entity_id' => $request->appointor_entity_id,
                'abn' => $request->abn,
                'acn' => $request->acn,
                'tfn' => $request->tfn, // Ensure proper encryption/security if stored
                'corporate_key' => $request->corporate_key,
                'registered_address' => $request->registered_address,
                'registered_email' => $request->registered_email,
                'phone_number' => $request->phone_number,
                'asic_renewal_date' => $request->asic_renewal_date,
                'bas_reporting_frequency' => $request->input('bas_reporting_frequency') ?: null,
                'uses_tax_agent' => $request->boolean('uses_tax_agent'),
                'gst_registered' => $request->has('gst_registered') ? $request->boolean('gst_registered') : true,
                'entity_tax_return_required' => $request->has('entity_tax_return_required')
                    ? $request->boolean('entity_tax_return_required')
                    : true,
                'user_id' => auth()->id(), // Associate with the logged-in user
                'status' => 'Active', // Default status
                'exclude_from_financial_reports' => $request->boolean('exclude_from_financial_reports'),
            ]);
        } catch (QueryException $e) {
            Log::error('BusinessEntity store failed', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['save' => 'Could not save the business entity. If ABN or ACN is already in use, use different values or edit the existing entity.']);
        }

        return redirect()->route('business-entities.index')->with('success', 'Business entity added successfully!');
    }

    /**
     * Display the specified business entity.
     *
     * @return View
     */
    public function show(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        $businessEntity->load(['appointorPerson', 'appointorEntity']);

        $assets = $businessEntity->assets;
        $persons = $businessEntity->persons()->with(['person', 'trusteeEntity'])->get();
        $entityBankAccountLinks = $businessEntity->bankAccountLinksForDisplay();
        $entityBankAccountGroups = $this->entityBankAccountHolderGroups($businessEntity, $entityBankAccountLinks);
        $operatingAccountIds = $entityBankAccountLinks
            ->filter(fn (BusinessEntityBankAccount $link) => in_array($link->purpose, BankAccount::ENTITY_OPERATING_PURPOSES, true))
            ->pluck('bank_account_id')
            ->unique()
            ->values();
        $bankAccounts = BankAccount::query()
            ->whereIn('id', $operatingAccountIds)
            ->with(['bankStatementEntries.transaction'])
            ->get();
        $portfolioBankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->with([
                'businessEntity',
                'holderEntity',
                'holderPerson',
                'entityPurposeLinks' => fn ($q) => $q->where('business_entity_id', $businessEntity->id),
            ])
            ->orderBy('account_name')
            ->get();
        $transactions = $businessEntity->transactions()->with(['bankStatementEntries', 'asset', 'relatedEntity', 'paymentDocument', 'vendor'])->orderBy('date', 'desc')->get();
        $invoices = Invoice::where('business_entity_id', $businessEntity->id)
            ->with(['asset'])
            ->orderByDesc('issue_date')
            ->get();
        $documentCategories = $businessEntity->documentCategories()
            ->whereNull('asset_id')
            ->with(['documents' => fn ($q) => $q->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $notes = $businessEntity->notes()->where('is_reminder', false)->orderBy('created_at', 'desc')->get();

        // Get unmatched transactions for each bank account
        $unmatchedTransactions = [];
        foreach ($bankAccounts as $bankAccount) {
            $unmatchedTransactions[$bankAccount->id] = $businessEntity->transactions()
                ->whereDoesntHave('bankStatementEntries')
                ->get();
        }

        // Get contact lists for the business entity
        $contactLists = $businessEntity->contactLists()->paginate(10);

        return view('business-entities.show', compact(
            'businessEntity',
            'assets',
            'persons',
            'bankAccounts',
            'entityBankAccountLinks',
            'entityBankAccountGroups',
            'portfolioBankAccounts',
            'transactions',
            'invoices',
            'documentCategories',
            'notes',
            'unmatchedTransactions',
            'contactLists'
        ));
    }

    /**
     * Display the main dashboard.
     *
     * @return View
     */
    public function dashboard()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::query()->operationalEntities()->get();
        $assets = Asset::query()
            ->whereIn('business_entity_id', $businessEntities->modelKeys())
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        // Fetch Reminder records (overdue by calendar date, or due in the next 15 days)
        $reminders = Reminder::query()
            ->active()
            ->dueOverdueOrWithinDays(15)
            ->with(['businessEntity', 'asset', 'user'])
            ->orderBy('next_due_date')
            ->get();

        // Fetch Note-based reminders
        $noteReminders = Note::where('is_reminder', true)
            ->where(function ($q) {
                $q->whereDate('reminder_date', '<', now()->startOfDay())
                    ->orWhere(function ($q2) {
                        $q2->whereDate('reminder_date', '>=', now()->startOfDay())
                            ->whereDate('reminder_date', '<=', now()->addDays(15));
                    });
            })
            ->with(['businessEntity', 'asset', 'user'])
            ->orderBy('reminder_date')
            ->get()
            ->map(function ($note) {
                // Normalize Note to match Reminder structure
                return (object) [
                    'id' => $note->id,
                    'content' => $note->content,
                    'next_due_date' => $note->reminder_date,
                    'repeat_type' => $note->repeat_type,
                    'repeat_end_date' => $note->repeat_end_date,
                    'business_entity_id' => $note->business_entity_id,
                    'asset_id' => $note->asset_id,
                    'user_id' => $note->user_id,
                    'created_at' => $note->created_at,
                    'businessEntity' => $note->businessEntity,
                    'asset' => $note->asset,
                    'user' => $note->user,
                    'is_note' => true, // Flag to identify Note-based reminder
                ];
            });

        $transactionDueReminders = Transaction::query()
            ->where('payment_status', 'unpaid')
            ->whereNotNull('due_date')
            ->where(function ($q) {
                $q->whereDate('due_date', '<', now()->startOfDay())
                    ->orWhere(function ($q2) {
                        $q2->whereDate('due_date', '>=', now()->startOfDay())
                            ->whereDate('due_date', '<=', now()->addDays(15));
                    });
            })
            ->with(['businessEntity.user', 'asset', 'vendor'])
            ->orderBy('due_date')
            ->get()
            ->map(function (Transaction $t) {
                $amt = number_format((float) $t->amount, 2);
                $desc = $t->description ?: 'Unpaid bill';
                $vendor = $t->vendor_display ? ' · '.$t->vendor_display : '';
                $content = 'Bill due: '.$desc.$vendor.' — $'.$amt;

                return (object) [
                    'content' => $content,
                    'next_due_date' => $t->due_date,
                    'repeat_type' => 'none',
                    'business_entity_id' => $t->business_entity_id,
                    'asset_id' => $t->asset_id,
                    'user' => $t->businessEntity?->user,
                    'businessEntity' => $t->businessEntity,
                    'asset' => $t->asset,
                    'is_note' => false,
                    'is_transaction' => true,
                    'transaction_id' => $t->id,
                ];
            });

        // Combine reminders, sort by due date
        $allReminders = $reminders->concat($noteReminders)->concat($transactionDueReminders)->sortByDesc('next_due_date')->values();

        $persons = EntityPerson::with(['person', 'trusteeEntity', 'businessEntity'])->get();

        // Group persons by their actual Person record to avoid duplicates
        $uniquePersons = $persons->where('person_id', '!=', null)
            ->groupBy('person_id')
            ->map(function ($entityPersonGroup) {
                $firstEntityPerson = $entityPersonGroup->first();

                return [
                    'person' => $firstEntityPerson->person,
                    'entityPersons' => $entityPersonGroup,
                    'totalRoles' => $entityPersonGroup->count(),
                    'activeRoles' => $entityPersonGroup->where('role_status', 'Active')->count(),
                    'resignedRoles' => $entityPersonGroup->where('role_status', 'Resigned')->count(),
                ];
            })
            ->values();

        $assetDueDateItems = Asset::upcomingDueDateRows();

        $entityDueDates = EntityPerson::whereNotNull('asic_due_date')
            ->whereDate('asic_due_date', '<=', now()->addDays(15))
            ->with('businessEntity')
            ->get();

        $payerOptions = TransactionPayerResolver::payerOptions();
        $vendors = Vendor::orderedForSelect();

        $commitmentSummary = app(CommitmentReportService::class)->dashboardSummary(
            $businessEntities->modelKeys()
        );

        return view('dashboard', compact(
            'businessEntities',
            'assets',
            'allReminders', // Pass combined reminders
            'persons',
            'uniquePersons',
            'assetDueDateItems',
            'entityDueDates',
            'payerOptions',
            'vendors',
            'commitmentSummary'
        ));
    }

    /**
     * Extract transaction information from an uploaded document using OpenAI.
     *
     * @return RedirectResponse
     */

    /**
     * Store a new transaction for a business entity.
     *
     * @return RedirectResponse
     */
    public function storeTransaction(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $this->normalizeOptionalTransactionAssetId($request);
        $this->normalizeOptionalRelatedEntityId($request);
        $this->normalizeOptionalVendorId($request);
        $this->normalizeEmptyGstBasisRequest($request);
        $this->normalizeOptionalBankAccountId($request);

        $resolvedEntityId = $request->filled('business_entity_id')
            ? (int) $request->business_entity_id
            : (int) $businessEntity->id;

        $this->prepareTransactionUploadValidation($request, ['document', 'payment_document']);

        $request->validate(array_merge([
            'business_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'invoice_number' => 'nullable|string|max:100',
            'transaction_type' => 'required|in:'.implode(',', array_keys(Transaction::allTypes())),
            'related_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $resolvedEntityId)),
            ],
            'gst_amount' => 'nullable|numeric',
            'gst_basis' => 'nullable|in:inclusive,exclusive',
            'document_name' => 'nullable|string|max:255',
            'payment_status' => 'required|in:unpaid,paid',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|in:'.implode(',', array_keys(Transaction::$paymentMethods)),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'payment_document_name' => 'nullable|string|max:255',
        ], $this->transactionReceiptUploadRules(true)), $this->transactionReceiptValidationMessages());

        Log::info('Dashboard add transaction: validation passed, persisting', $this->storeTransactionRequestLogContext($request, $businessEntity));

        try {
            $targetEntity = $request->filled('business_entity_id')
                ? BusinessEntity::findOrFail($request->integer('business_entity_id'))
                : $businessEntity;

            $this->ensureOperationalForAccounting($targetEntity);

            $this->authorize('update', $targetEntity);

            if ($request->filled('related_entity_id')
                && (int) $request->related_entity_id === (int) $targetEntity->id) {
                throw ValidationException::withMessages([
                    'related_entity_id' => 'Related entity must be different from the business entity.',
                ]);
            }

            $this->validateTransactionGstBasis($request);

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
            $bankAccountId = $this->resolveBankAccountIdForTransactionSave($request);
            $vendorData = $this->resolveTransactionVendorData($request);

            $transaction = DB::transaction(function () use ($request, $targetEntity, $asset, $gstResolved, $paidBy, $bankAccountId, $vendorData) {
                $receiptPath = null;
                $documentId = null;
                $prefillPath = $request->input('receipt_path');

                if ($request->hasFile('document')) {
                    $file = $request->file('document');
                    $originalName = $file->getClientOriginalName();
                    $displayName = $this->buildReceiptUploadDisplayName($request, $file);
                    $labelBase = $request->filled('document_name')
                        ? trim((string) $request->input('document_name'))
                        : pathinfo($originalName, PATHINFO_FILENAME);
                    $desc = trim('Transaction receipt'.($request->description ? ': '.$request->description : ''));
                    $document = $this->documentUploadService->createTransactionReceiptDocumentFromUpload(
                        $targetEntity,
                        $asset,
                        $file,
                        $displayName,
                        $labelBase ?: 'Receipt',
                        $desc !== '' ? $desc : null
                    );
                    $receiptPath = $document->path;
                    $documentId = $document->id;
                } elseif (
                    $prefillPath
                    && $this->prefillReceiptPathAllowedForEntity($prefillPath, $targetEntity)
                    && Storage::disk('s3')->exists($prefillPath)
                ) {
                    $displayName = basename(str_replace('\\', '/', $prefillPath));
                    $labelBase = pathinfo($displayName, PATHINFO_FILENAME) ?: 'Receipt';
                    $desc = trim('Transaction receipt'.($request->description ? ': '.$request->description : ''));
                    $document = $this->documentUploadService->createTransactionReceiptFromExistingS3Path(
                        $targetEntity,
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
                        $targetEntity,
                        $asset,
                        $payFile,
                        $payDisplayName,
                        $payLabelBase ?: 'Payment Receipt',
                        $payDesc !== '' ? $payDesc : null
                    );
                    $paymentDocumentId = $payDocument->id;
                }

                return Transaction::create([
                    'business_entity_id' => $targetEntity->id,
                    'asset_id' => $request->filled('asset_id') ? $request->integer('asset_id') : null,
                    'related_entity_id' => $request->related_entity_id,
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
                    'payment_status' => $request->payment_status ?? 'paid',
                    'due_date' => $request->due_date,
                    'paid_at' => $request->paid_at,
                    'payment_method' => $request->payment_method,
                    'paid_by' => $paidBy,
                    'bank_account_id' => $bankAccountId,
                    'payment_document_id' => $paymentDocumentId,
                ]);
            });

            Log::info('Dashboard add transaction: saved', [
                'transaction_id' => $transaction->id,
                'target_business_entity_id' => $transaction->business_entity_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('dashboard')->with('success', "Transaction '{$transaction->description}' added successfully!");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $context = $this->storeTransactionRequestLogContext($request, $businessEntity);
            if ($e instanceof QueryException) {
                $context['sql'] = $e->getSql();
                $context['bindings'] = $e->getBindings();
            }
            Log::error('Dashboard add transaction: failed', array_merge($context, [
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            return redirect()
                ->route('dashboard')
                ->withInput()
                ->with('error', 'Could not save this transaction. Please try again.');
        }
    }

    /**
     * Store a new transaction for a bank account.
     *
     * @return RedirectResponse
     */
    public function storeBankTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization check
        if (! $bankAccount->canUseForTransaction($businessEntity)) {
            abort(403, 'Unauthorized');
        }

        $this->normalizeOptionalTransactionAssetId($request);
        $this->normalizeOptionalRelatedEntityId($request);
        $this->normalizeOptionalVendorId($request);
        $this->normalizeEmptyGstBasisRequest($request);

        $this->prepareTransactionUploadValidation($request, ['document', 'payment_document']);

        // Validate the transaction data
        $request->validate(array_merge([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'invoice_number' => 'nullable|string|max:100',
            'transaction_type' => 'required|in:'.implode(',', array_keys(Transaction::allTypes())),
            'related_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $businessEntity->id)),
            ],
            'gst_amount' => 'nullable|numeric',
            'gst_basis' => 'nullable|in:inclusive,exclusive',
            'document_name' => 'nullable|string|max:255',
            'payment_status' => 'required|in:unpaid,paid',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|in:'.implode(',', array_keys(Transaction::$paymentMethods)),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'payment_document_name' => 'nullable|string|max:255',
        ], $this->transactionReceiptUploadRules(true)), $this->transactionReceiptValidationMessages());

        $this->validateTransactionGstBasis($request);

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
        $vendorData = $this->resolveTransactionVendorData($request);

        $transaction = DB::transaction(function () use ($request, $businessEntity, $bankAccount, $asset, $gstResolved, $paidBy, $vendorData) {
            $receiptPath = null;
            $documentId = null;
            $prefillPath = $request->input('receipt_path');

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $originalName = $file->getClientOriginalName();
                $displayName = $this->buildReceiptUploadDisplayName($request, $file);
                $labelBase = $request->filled('document_name')
                    ? trim((string) $request->input('document_name'))
                    : pathinfo($originalName, PATHINFO_FILENAME);
                $desc = trim('Transaction receipt'.($request->description ? ': '.$request->description : ''));
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
                $prefillPath
                && $this->prefillReceiptPathAllowedForEntity($prefillPath, $businessEntity)
                && Storage::disk('s3')->exists($prefillPath)
            ) {
                $displayName = basename(str_replace('\\', '/', $prefillPath));
                $labelBase = pathinfo($displayName, PATHINFO_FILENAME) ?: 'Receipt';
                $desc = trim('Transaction receipt'.($request->description ? ': '.$request->description : ''));
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
                'related_entity_id' => $request->related_entity_id,
                'bank_account_id' => $bankAccount->id,
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
                'payment_status' => $request->payment_status ?? 'paid',
                'due_date' => $request->due_date,
                'paid_at' => $request->paid_at,
                'payment_method' => $request->payment_method,
                'paid_by' => $paidBy,
                'payment_document_id' => $paymentDocumentId,
            ]);
        });

        // Redirect to the bank account show page with success message
        return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
            ->with('success', "Transaction '{$transaction->description}' added successfully!");
    }

    /**
     * Show the form for editing the specified transaction.
     *
     * @return View|RedirectResponse
     */
    public function editTransaction(BusinessEntity $businessEntity, Transaction $transaction)
    {
        $this->authorize('view', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization check: ensure the transaction belongs to the business entity
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->load('asset');

        $payerOptions = TransactionPayerResolver::payerOptions();
        $vendors = Vendor::orderedForSelect();

        return view('business-entities.bank-accounts.transactions.edit', compact('businessEntity', 'transaction', 'payerOptions', 'vendors'));
    }

    /**
     * Update the specified transaction in storage.
     *
     * @return RedirectResponse
     */
    public function updateTransaction(Request $request, BusinessEntity $businessEntity, Transaction $transaction)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization check
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->normalizeOptionalTransactionAssetId($request);
        $this->normalizeOptionalRelatedEntityId($request);
        $this->normalizeOptionalVendorId($request);
        $this->normalizeEmptyGstBasisRequest($request);
        $this->normalizeOptionalBankAccountId($request);

        $this->prepareTransactionUploadValidation($request, ['payment_document']);

        $data = $request->validate(array_merge([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'invoice_number' => 'nullable|string|max:100',
            'transaction_type' => 'required|in:'.implode(',', array_keys(Transaction::allTypes())),
            'related_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $businessEntity->id)),
            ],
            'gst_amount' => 'nullable|numeric',
            'gst_basis' => 'nullable|in:inclusive,exclusive',
            'payment_status' => 'required|in:unpaid,paid',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|in:'.implode(',', array_keys(Transaction::$paymentMethods)),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'payment_document_name' => 'nullable|string|max:255',
        ], $this->transactionReceiptUploadRules(false)), $this->transactionReceiptValidationMessages());

        $this->validateTransactionGstBasis($request);

        $gstResolved = TransactionGstResolver::resolve(
            (float) $data['amount'],
            $data['gst_basis'] ?? null,
            $request->input('gst_amount'),
            Transaction::directionFromType((string) $data['transaction_type'])
        );

        $data['asset_id'] = $request->filled('asset_id') ? (int) $data['asset_id'] : null;
        $data['related_entity_id'] = $request->filled('related_entity_id') ? (int) $data['related_entity_id'] : null;
        $vendorData = $this->resolveTransactionVendorData($request);
        $data['vendor_id'] = $vendorData['vendor_id'];
        $data['vendor_name'] = $vendorData['vendor_name'];

        $this->detachIncompatibleReceiptDocument($transaction, $data['asset_id']);

        $asset = $data['asset_id'] ? Asset::query()->find($data['asset_id']) : null;

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
            $data['payment_document_id'] = $payDocument->id;
        }

        $paidBy = $this->validatedPaidBy($request);
        $bankAccountId = $this->resolveBankAccountIdForTransactionSave($request, $transaction);

        $transaction->update(array_merge(
            Arr::only($data, [
                'date', 'amount', 'description', 'vendor_id', 'vendor_name', 'invoice_number', 'transaction_type',
                'related_entity_id', 'asset_id',
                'payment_status', 'due_date', 'paid_at', 'payment_method',
                'payment_document_id',
            ]),
            [
                'gst_amount' => $gstResolved['gst_amount'],
                'gst_status' => $gstResolved['gst_status'],
                'gst_basis' => $gstResolved['gst_basis'],
                'paid_by' => $paidBy,
                'bank_account_id' => $bankAccountId,
            ]
        ));

        return redirect()->route('business-entities.show', $businessEntity->id)->with('success', 'Transaction updated successfully!');
    }

    /**
     * Remove a transaction; optionally remove its linked receipt document.
     */
    public function destroyTransaction(Request $request, BusinessEntity $businessEntity, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $businessEntity);

        if ((int) $transaction->business_entity_id !== (int) $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        $deleteLinkedDocument = $request->boolean('delete_linked_document');
        $document = $transaction->document_id
            ? Document::query()->find($transaction->document_id)
            : null;
        $paymentDocument = $transaction->payment_document_id
            ? Document::query()->find($transaction->payment_document_id)
            : null;

        $transaction->delete();

        if ($deleteLinkedDocument && $document) {
            if ($document->path && Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
            $document->delete();
        }

        if ($deleteLinkedDocument && $paymentDocument) {
            if ($paymentDocument->path && Storage::disk('s3')->exists($paymentDocument->path)) {
                Storage::disk('s3')->delete($paymentDocument->path);
            }
            $paymentDocument->delete();
        }

        return redirect()->route('business-entities.show', $businessEntity->id)
            ->withFragment('tab_transactions')
            ->with('success', 'Transaction deleted.');
    }

    /**
     * Update the specified bank account transaction.
     *
     * @return RedirectResponse
     */
    public function updateBankTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount, Transaction $transaction)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization checks
        if ($transaction->business_entity_id !== $businessEntity->id || $transaction->bank_account_id !== $bankAccount->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->normalizeOptionalTransactionAssetId($request);
        $this->normalizeOptionalRelatedEntityId($request);
        $this->normalizeOptionalVendorId($request);
        $this->normalizeEmptyGstBasisRequest($request);

        $this->prepareTransactionUploadValidation($request, ['payment_document']);

        $data = $request->validate(array_merge([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'invoice_number' => 'nullable|string|max:100',
            'transaction_type' => 'required|in:'.implode(',', array_keys(Transaction::allTypes())),
            'related_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('business_entity_id', $businessEntity->id)),
            ],
            'gst_amount' => 'nullable|numeric',
            'gst_basis' => 'nullable|in:inclusive,exclusive',
            'payment_status' => 'required|in:unpaid,paid',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|in:'.implode(',', array_keys(Transaction::$paymentMethods)),
            'paid_by_select' => ['nullable', 'string', 'max:255'],
            'paid_by_other' => ['nullable', 'string', 'max:255'],
            'payment_document_name' => 'nullable|string|max:255',
        ], $this->transactionReceiptUploadRules(false)), $this->transactionReceiptValidationMessages());

        $this->validateTransactionGstBasis($request);

        $gstResolved = TransactionGstResolver::resolve(
            (float) $data['amount'],
            $data['gst_basis'] ?? null,
            $request->input('gst_amount'),
            Transaction::directionFromType((string) $data['transaction_type'])
        );

        $data['asset_id'] = $request->filled('asset_id') ? (int) $data['asset_id'] : null;
        $data['related_entity_id'] = $request->filled('related_entity_id') ? (int) $data['related_entity_id'] : null;
        $vendorData = $this->resolveTransactionVendorData($request);
        $data['vendor_id'] = $vendorData['vendor_id'];
        $data['vendor_name'] = $vendorData['vendor_name'];

        $this->detachIncompatibleReceiptDocument($transaction, $data['asset_id']);

        $asset = $data['asset_id'] ? Asset::query()->find($data['asset_id']) : null;

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
            $data['payment_document_id'] = $payDocument->id;
        }

        $paidBy = $this->validatedPaidBy($request);

        $transaction->update(array_merge(
            Arr::only($data, [
                'date', 'amount', 'description', 'vendor_id', 'vendor_name', 'invoice_number', 'transaction_type',
                'related_entity_id', 'asset_id',
                'payment_status', 'due_date', 'paid_at', 'payment_method',
                'payment_document_id',
            ]),
            [
                'gst_amount' => $gstResolved['gst_amount'],
                'gst_status' => $gstResolved['gst_status'],
                'gst_basis' => $gstResolved['gst_basis'],
                'paid_by' => $paidBy,
            ]
        ));

        return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
            ->with('success', 'Transaction updated successfully!');
    }

    /**
     * Match a transaction to a bank statement entry.
     *
     * @return RedirectResponse
     */
    public function matchTransaction(Request $request, BusinessEntity $businessEntity, Transaction $transaction)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization check
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the bank statement entry ID
        $request->validate([
            'bank_statement_entry_id' => 'required|exists:bank_statement_entries,id',
        ]);

        // Find the bank statement entry
        $entry = BankStatementEntry::with('bankAccount')->findOrFail($request->bank_statement_entry_id);

        // Further authorization: ensure the bank statement entry belongs to the same business entity
        if (! $entry->bankAccount || ! $entry->bankAccount->canUseForBankImport($businessEntity)) {
            abort(403, 'Bank statement entry does not belong to this business entity.');
        }

        // Update the bank statement entry to link it to the transaction
        $entry->update(['transaction_id' => $transaction->id]);
        // Optionally, update the transaction's bank_account_id if it's null
        if (is_null($transaction->bank_account_id)) {
            $transaction->update(['bank_account_id' => $entry->bank_account_id]);
        }

        // Redirect back to the entity show page (likely to the bank accounts tab)
        return $this->redirectToBusinessEntityShow($businessEntity, $entry->bank_account_id, 'tab_bank_accounts')
            ->with('success', 'Transaction matched successfully!');
    }

    /**
     * Get bank accounts for a specific business entity (useful for AJAX calls).
     *
     * @return JsonResponse
     */
    public function getBankAccounts(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        if ($businessEntity->isTenancyContactOnly()) {
            return response()->json([]);
        }

        $purpose = $request->query('purpose');

        if ($purpose && ! in_array($purpose, BankAccount::PURPOSES, true)) {
            return response()->json([], 422);
        }

        $forTransaction = $request->boolean('for_transaction');

        $query = BankAccount::query()
            ->select('id', 'account_name', 'bank_name', 'bsb', 'account_number', 'account_purpose', 'business_entity_id', 'user_id');

        if ($purpose === BankAccount::PURPOSE_LOAN || $purpose === BankAccount::PURPOSE_LOAN_REPAYMENT) {
            $query->forLoanAssetLinkPicker($businessEntity);
        } else {
            $accountIds = BusinessEntityBankAccount::query()
                ->where('business_entity_id', $businessEntity->id)
                ->when($purpose, fn ($q) => $q->where('purpose', $purpose))
                ->pluck('bank_account_id');

            $query->where(function ($q) use ($businessEntity, $purpose, $accountIds) {
                if ($accountIds->isNotEmpty()) {
                    $q->whereIn('id', $accountIds);
                }

                $q->orWhere(function ($inner) use ($businessEntity, $purpose) {
                    $inner->where('business_entity_id', $businessEntity->id);

                    if ($purpose) {
                        $inner->where('account_purpose', $purpose);
                    } else {
                        $inner->whereIn('account_purpose', BankAccount::ENTITY_PURPOSES);
                    }
                });
            });
        }

        $accounts = $query->orderBy('account_name')->get();

        if ($forTransaction) {
            $accounts = $accounts
                ->filter(fn (BankAccount $account) => $account->canUseForTransaction($businessEntity))
                ->values();
        }

        return response()->json(
            $accounts->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'bank_name' => $account->bank_name,
                'bsb' => BankAccount::formatBsb($account->bsb),
                'account_purpose' => $account->account_purpose,
                'label' => $forTransaction ? $account->transactionAccountLabel() : $account->displayLabel(),
            ])
        );
    }

    /**
     * Store a new note for a business entity.
     *
     * @return RedirectResponse
     */
    public function storeNote(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        // Validate note content and reminder details
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000', // Max length for note content
                'is_reminder' => 'boolean', // Ensure it's treated as boolean
                'reminder_date' => 'nullable|required_if:is_reminder,1|date|after_or_equal:today', // Required only if it's a reminder
            ], [
                // Custom error messages for clarity
                'reminder_date.required_if' => 'The reminder date is required when setting a note as a reminder.',
                'reminder_date.date' => 'The reminder date must be a valid date.',
                'reminder_date.after_or_equal' => 'The reminder date must be today or a future date.',
            ]);
        } catch (ValidationException $e) {
            Log::error('Note validation failed: ', $e->errors());

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $isReminder = $request->boolean('is_reminder'); // Use boolean() helper

        // Prepare data for note creation
        $noteData = [
            'content' => $request->content,
            'business_entity_id' => $businessEntity->id,
            'user_id' => auth()->id(), // Associate with logged-in user
            'is_reminder' => $isReminder,
            'reminder_date' => $isReminder ? $request->reminder_date : null, // Set date only if it's a reminder
        ];

        // Create the note
        try {
            $note = Note::create($noteData);
        } catch (\Exception $e) {
            Log::error('Failed to save note: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to save note. Please try again.')->withInput();
        }

        // Redirect back with success message
        if ($request->expectsJson()) {
            $note->load('user');

            return response()->json([
                'status' => true,
                'message' => 'Note added successfully.',
                'list_html' => view('business-entities.partials.notes.list', [
                    'businessEntity' => $businessEntity,
                    'notes' => $businessEntity->notes()->where('is_reminder', false)->orderByDesc('created_at')->get(),
                ])->render(),
            ]);
        }

        return redirect()->back()->withFragment('tab_notes')->with('success', 'Note added successfully!');
    }

    /**
     * Show the form for editing the specified business entity.
     *
     * @return View
     */
    public function edit(BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $persons = Person::query()->orderBy('id')->get();

        $businessEntities = BusinessEntity::query()
            ->operationalEntities()
            ->where('entity_type', '!=', 'Trust')
            ->orderBy('legal_name')
            ->get();

        return view('business-entities.edit', compact('businessEntity', 'persons', 'businessEntities'));
    }

    /**
     * Update the specified business entity in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        // Validate the incoming request data, ensuring uniqueness checks ignore the current entity
        $request->validate([
            'legal_name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'abn' => ['nullable', 'string', 'max:11', new UniqueAbnHash($businessEntity->id)],
            'acn' => ['nullable', 'string', 'max:9', new UniqueAcnHash($businessEntity->id)],
            'tfn' => 'nullable|string|max:9',
            'corporate_key' => 'nullable|string|max:255',
            'registered_address' => 'required|string',
            'registered_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:15',
            'asic_renewal_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive,Deregistered',
            'exclude_from_financial_reports' => 'nullable|boolean',
            'bas_reporting_frequency' => 'nullable|in:annual,quarterly,monthly',
            'uses_tax_agent' => 'nullable|boolean',
            'gst_registered' => 'nullable|boolean',
            'entity_tax_return_required' => 'nullable|boolean',
            'trust_type' => 'nullable|required_if:entity_type,Trust|in:Discretionary,Unit,Fixed,Testamentary,Charitable',
            'trust_establishment_date' => 'nullable|required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_date' => 'nullable|required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_reference' => 'nullable|string|max:255',
            'trust_vesting_date' => 'nullable|date|after:trust_establishment_date',
            'trust_vesting_conditions' => 'nullable|string|max:1000',
            'appointor_type' => 'nullable|required_if:entity_type,Trust|in:person,entity',
            'appointor_person_id' => [
                'nullable',
                'required_if:appointor_type,person',
                Rule::exists('persons', 'id'),
            ],
            'appointor_entity_id' => [
                'nullable',
                'required_if:appointor_type,entity',
                BusinessEntity::ruleExistsOperationalAppointorCompany(),
            ],
        ], [
            'trust_type.required_if' => 'Trust type is required when entity type is Trust.',
            'trust_establishment_date.required_if' => 'Trust establishment date is required when entity type is Trust.',
            'trust_deed_date.required_if' => 'Trust deed date is required when entity type is Trust.',
            'appointor_type.required_if' => 'Appointor type is required when entity type is Trust.',
            'appointor_person_id.required_if' => 'Please select an appointor person.',
            'appointor_entity_id.required_if' => 'Please select an appointor entity.',
        ]);

        $isTrust = $request->entity_type === 'Trust';

        // Update the business entity with validated data
        $payload = [
            'legal_name' => $request->legal_name,
            'trading_name' => $request->trading_name,
            'entity_type' => $request->entity_type,
            'trust_type' => $isTrust ? $request->trust_type : null,
            'trust_establishment_date' => $isTrust ? $request->trust_establishment_date : null,
            'trust_deed_date' => $isTrust ? $request->trust_deed_date : null,
            'trust_deed_reference' => $isTrust ? $request->trust_deed_reference : null,
            'trust_vesting_date' => $isTrust ? $request->trust_vesting_date : null,
            'trust_vesting_conditions' => $isTrust ? $request->trust_vesting_conditions : null,
            'appointor_person_id' => $isTrust && $request->appointor_type === 'person' ? $request->appointor_person_id : null,
            'appointor_entity_id' => $isTrust && $request->appointor_type === 'entity' ? $request->appointor_entity_id : null,
            'abn' => $request->abn,
            'acn' => $request->acn,
            'tfn' => $request->tfn, // Ensure proper encryption/security
            'corporate_key' => $request->corporate_key,
            'registered_address' => $request->registered_address,
            'registered_email' => $request->registered_email,
            'phone_number' => $request->phone_number,
            'asic_renewal_date' => $request->asic_renewal_date,
            'status' => $request->status, // Update status
            'exclude_from_financial_reports' => $request->boolean('exclude_from_financial_reports'),
        ];

        // Profile workspace form omits these; only persist when the compliance section is submitted.
        if ($request->has('bas_reporting_frequency')) {
            $payload['bas_reporting_frequency'] = $request->input('bas_reporting_frequency') ?: null;
            $payload['uses_tax_agent'] = $request->boolean('uses_tax_agent');
            $payload['gst_registered'] = $request->boolean('gst_registered');
            $payload['entity_tax_return_required'] = $request->boolean('entity_tax_return_required');
        }

        $businessEntity->update($payload);
        if ($request->expectsJson()) {
            $businessEntity->refresh();

            return response()->json([
                'status' => true,
                'message' => 'Business entity updated successfully.',
                'sidebar_html' => view('business-entities.partials.entity-details-sidebar', [
                    'businessEntity' => $businessEntity->fresh(['appointorPerson', 'appointorEntity']),
                ])->render(),
                'entity' => [
                    'legal_name' => $businessEntity->legal_name,
                    'entity_type' => $businessEntity->entity_type,
                ],
            ]);
        }

        // Redirect to the show page for the updated entity with success message
        return redirect()->route('business-entities.show', $businessEntity->id)->with('success', 'Business entity updated successfully!');
    }

    // --- Bank Account Methods ---

    /**
     * Show the form for creating a new bank account for a business entity.
     *
     * @return View
     */
    public function createBankAccount(BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = $this->personOptionsForHolder();
        $leasableAssets = $this->bankAccountAssetLinkService->leasableAssetsForEntity($businessEntity);

        return view('business-entities.bank-accounts.create', compact('businessEntity', 'businessEntities', 'persons', 'leasableAssets'));
    }

    /**
     * Store a newly created bank account for a business entity.
     *
     * @return RedirectResponse
     */
    public function storeBankAccount(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $this->mergeBankNameFromRequest($request);
        $this->forgetRentCollectionAssetIdsUnlessRentReceiving($request);

        // Validate bank account details
        $validated = $request->validate(array_merge(
            $this->entityBankAccountValidationRules(),
            $this->bankAccountAssetLinkService->rentCollectionAssetValidationRules()
        ));

        $bankAccount = $businessEntity->bankAccounts()->create(
            $this->bankAccountAttributesFromRequest($validated, $businessEntity)
        );

        BusinessEntityBankAccount::firstOrCreate([
            'business_entity_id' => $businessEntity->id,
            'bank_account_id' => $bankAccount->id,
            'purpose' => $validated['account_purpose'],
        ]);

        $message = 'Bank account added successfully!';
        if ($validated['account_purpose'] === BankAccount::PURPOSE_RENT_RECEIVING) {
            $linked = $this->bankAccountAssetLinkService->linkRentCollectionToAssets(
                $bankAccount,
                $businessEntity,
                $validated['rent_collection_asset_ids'] ?? []
            );
            if ($linked > 0) {
                $message .= ' Linked as Rent Paid Into on '.$linked.' asset'.($linked === 1 ? '' : 's').'.';
            }
        }

        if ($request->expectsJson()) {
            return $this->bankAccountWorkspaceJsonResponse($businessEntity, $message);
        }

        return $this->redirectToBusinessEntityShow($businessEntity, null, 'tab_bank_accounts')
            ->with('success', $message);
    }

    /**
     * Attach an existing portfolio account to this entity with a purpose (same account may have multiple purposes).
     *
     * @return RedirectResponse
     */
    public function assignBankAccountToEntity(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $this->forgetRentCollectionAssetIdsUnlessRentReceiving($request);

        $validated = $request->validate(array_merge([
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'account_purpose' => ['required', Rule::in(BankAccount::ENTITY_PURPOSES)],
        ], $this->bankAccountAssetLinkService->rentCollectionAssetValidationRules()));

        $bankAccount = BankAccount::query()
            ->visibleInPortfolio()
            ->find($validated['bank_account_id']);

        if ($bankAccount === null || ! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }

        $purpose = $validated['account_purpose'];

        if (! $bankAccount->canAttachPurposeToEntity($businessEntity, $purpose)) {
            if (! $bankAccount->canReceiveEntityPurposeLinks()) {
                $message = 'Portfolio lender accounts cannot be attached to an entity.';
            } else {
                $message = 'This account already has purpose '.BankAccount::purposeLabel($purpose).' on this entity.';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                ], 422);
            }

            return $this->redirectToBusinessEntityShow($businessEntity, null, 'tab_bank_accounts')
                ->with('error', $message);
        }

        BusinessEntityBankAccount::firstOrCreate([
            'business_entity_id' => $businessEntity->id,
            'bank_account_id' => $bankAccount->id,
            'purpose' => $purpose,
        ]);

        if ($bankAccount->business_entity_id === null) {
            $bankAccount->update([
                'business_entity_id' => $businessEntity->id,
                'user_id' => $businessEntity->user_id ?? auth()->id(),
            ]);
        }

        $message = 'Bank account attached as '.BankAccount::purposeLabel($purpose).'.';
        if ($purpose === BankAccount::PURPOSE_RENT_RECEIVING) {
            $linked = $this->bankAccountAssetLinkService->linkRentCollectionToAssets(
                $bankAccount,
                $businessEntity,
                $validated['rent_collection_asset_ids'] ?? []
            );
            if ($linked > 0) {
                $message .= ' Linked as Rent Paid Into on '.$linked.' asset'.($linked === 1 ? '' : 's').'.';
            }
        }

        if ($request->expectsJson()) {
            return $this->bankAccountWorkspaceJsonResponse($businessEntity, $message);
        }

        return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
            ->with('success', $message);
    }

    /**
     * Sync assets that use this rent-receiving account as Rent Paid Into.
     */
    public function syncRentCollectionAssets(
        Request $request,
        BusinessEntity $businessEntity,
        BusinessEntityBankAccount $bankAccountLink
    ) {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        if ((int) $bankAccountLink->business_entity_id !== (int) $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($bankAccountLink->purpose !== BankAccount::PURPOSE_RENT_RECEIVING) {
            throw ValidationException::withMessages([
                'rent_collection_asset_ids' => 'Asset links can only be managed for rent receiving accounts.',
            ]);
        }

        $validated = $request->validate(
            $this->bankAccountAssetLinkService->rentCollectionAssetValidationRules()
        );

        $bankAccount = $bankAccountLink->bankAccount;
        if ($bankAccount === null) {
            abort(404);
        }

        $result = $this->bankAccountAssetLinkService->syncRentCollectionAssets(
            $bankAccount,
            $businessEntity,
            $validated['rent_collection_asset_ids'] ?? []
        );

        $parts = [];
        if ($result['linked'] > 0) {
            $parts[] = 'linked '.$result['linked'].' asset'.($result['linked'] === 1 ? '' : 's');
        }
        if ($result['unlinked'] > 0) {
            $parts[] = 'unlinked '.$result['unlinked'];
        }
        $message = $parts === []
            ? 'Rent asset links updated.'
            : 'Rent asset links updated ('.implode(', ', $parts).').';

        if ($request->expectsJson()) {
            return $this->bankAccountWorkspaceJsonResponse($businessEntity, $message);
        }

        return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
            ->with('success', $message);
    }

    /**
     * Remove one entity purpose link (does not delete the underlying bank account).
     *
     * @return RedirectResponse
     */
    public function detachBankAccountLink(BusinessEntity $businessEntity, BusinessEntityBankAccount $bankAccountLink)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        if ((int) $bankAccountLink->business_entity_id !== (int) $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        $message = 'Bank account purpose removed from this entity.';

        if ($bankAccountLink->purpose === BankAccount::PURPOSE_RENT_RECEIVING && $bankAccountLink->bankAccount) {
            $unlinked = $this->bankAccountAssetLinkService->unlinkAllRentCollectionAssetsForAccount(
                $bankAccountLink->bankAccount,
                $businessEntity
            );
            if ($unlinked > 0) {
                $message .= ' Also cleared Rent Paid Into on '.$unlinked.' asset'.($unlinked === 1 ? '' : 's').'.';
            }
        }

        $bankAccountLink->delete();

        if (request()->expectsJson()) {
            return $this->bankAccountWorkspaceJsonResponse($businessEntity, $message);
        }

        return $this->redirectToBusinessEntityShow($businessEntity, null, 'tab_bank_accounts')
            ->with('success', $message);
    }

    /**
     * Show the form for editing the specified bank account.
     *
     * @return View|RedirectResponse
     */
    public function editBankAccount(BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $this->ensureBankAccountAccessibleOnEntity($businessEntity, $bankAccount);

        $bankAccount->load(['holderEntity', 'holderPerson']);
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = $this->personOptionsForHolder();

        SecurityAuditLogger::bankAccountNumberViewed(auth()->user(), $bankAccount, 'edit_form');

        return view('business-entities.bank-accounts.edit', compact('businessEntity', 'bankAccount', 'businessEntities', 'persons'));
    }

    /**
     * Update the specified bank account in storage.
     *
     * @return RedirectResponse
     */
    public function updateBankAccount(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $this->ensureBankAccountAccessibleOnEntity($businessEntity, $bankAccount);

        $this->mergeBankNameFromRequest($request);

        // Validate the updated bank account details
        $validated = $request->validate($this->entityBankAccountValidationRules());

        $attributes = $this->bankAccountAttributesFromRequest($validated, $businessEntity);
        if ($bankAccount->business_entity_id !== null
            && (int) $bankAccount->business_entity_id !== (int) $businessEntity->id) {
            unset($attributes['business_entity_id']);
        }

        $bankAccount->update($attributes);

        if ($request->expectsJson()) {
            return $this->bankPanelJsonResponse($request, 'Bank account updated successfully.');
        }

        // Redirect back to the entity show page (bank accounts tab) with success message
        return $this->redirectToBusinessEntityShow($businessEntity, null, 'tab_bank_accounts')
            ->with('success', 'Bank account updated successfully!');
    }

    /**
     * Allocate a bank statement entry to an existing transaction.
     *
     * @return RedirectResponse
     */
    public function allocateTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount, BankStatementEntry $bankStatementEntry)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization checks
        if ($bankStatementEntry->bank_account_id !== $bankAccount->id || ! $bankAccount->canUseForTransaction($businessEntity)) {
            abort(403, 'Unauthorized action.');
        }

        // Validate that a transaction ID is provided (or null to unallocate)
        $request->validate([
            'transaction_id' => 'nullable|exists:transactions,id',
        ]);

        $transactionId = $request->input('transaction_id');

        // If allocating, ensure the selected transaction belongs to the same business entity
        if ($transactionId) {
            $transaction = Transaction::find($transactionId);
            if (! $transaction || $transaction->business_entity_id !== $businessEntity->id) {
                return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
                    ->with('error', 'Selected transaction does not belong to this business entity.');
            }
            // Update the transaction's bank_account_id if it's not already set
            if (is_null($transaction->bank_account_id)) {
                $transaction->update(['bank_account_id' => $bankAccount->id]);
            } elseif ($transaction->bank_account_id !== $bankAccount->id) {
                // Handle case where transaction is already linked to a different account (optional)
                Log::warning("Transaction {$transactionId} allocated to BankStatementEntry {$bankStatementEntry->id} but already belongs to BankAccount {$transaction->bank_account_id}.");
            }
        }

        // Update the bank statement entry's transaction link
        $bankStatementEntry->update([
            'transaction_id' => $transactionId ?: null, // Set to null if unallocating
        ]);

        $message = $transactionId ? 'Transaction allocated successfully!' : 'Transaction unallocated successfully!';

        // Redirect back with success message
        return $this->redirectToBusinessEntityShow($businessEntity, $bankAccount->id, 'tab_bank_accounts')
            ->with('success', $message);
    }

    /**
     * Show the form for creating a new transaction, potentially pre-filled from receipt extraction.
     *
     * @return View|RedirectResponse
     */
    public function createTransaction(BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        // Authorization check
        if (! $bankAccount->canUseForTransaction($businessEntity)) {
            abort(403, 'Unauthorized');
        }

        $businessEntities = BusinessEntity::query()
            ->operationalEntities()
            ->orderBy('legal_name')
            ->get();

        // Retrieve pre-filled data from session if redirected from receipt extraction
        $transactionData = session('transactionData', [
            'date' => now()->toDateString(),
            'amount' => '',
            'description' => '',
            'vendor_id' => '',
            'vendor_name' => '',
            'invoice_number' => '',
            'transaction_type' => '',
            'gst_amount' => '',
            'gst_basis' => '',
            'receipt_path' => '',
            'asset_id' => '',
            'payment_status' => 'paid',
            'due_date' => '',
            'paid_at' => '',
            'payment_method' => '',
            'paid_by' => '',
            'direction' => 'expense',
        ]);

        if (empty($transactionData['vendor_id']) && ! empty($transactionData['vendor_name'])) {
            $matchedVendorId = Vendor::query()
                ->where('name', $transactionData['vendor_name'])
                ->value('id');
            if ($matchedVendorId) {
                $transactionData['vendor_id'] = $matchedVendorId;
            }
        }

        $payerOptions = TransactionPayerResolver::payerOptions();
        $vendors = Vendor::orderedForSelect();

        return view('business-entities.bank-accounts.transactions.create', compact(
            'businessEntity', 'bankAccount', 'businessEntities', 'transactionData', 'payerOptions', 'vendors'
        ));
    }

    /**
     * Display the specified transaction.
     *
     * @return View|RedirectResponse
     */
    public function showTransaction(BusinessEntity $businessEntity, BankAccount $bankAccount, Transaction $transaction)
    {
        $this->authorize('view', $businessEntity);

        // Authorization checks
        if ($transaction->bank_account_id !== $bankAccount->id || ! $bankAccount->canUseForTransaction($businessEntity)) {
            abort(404); // Or abort(403) if preferred
        }

        $transaction->load(['asset', 'bankAccount']);

        return view('business-entities.bank-accounts.transactions.show', compact('businessEntity', 'bankAccount', 'transaction'));
    }

    /**
     * Finalize a reminder by removing its reminder status.
     *
     * @return RedirectResponse
     */
    public function finalizeReminder(Note $note)
    {
        $note->update(['reminder_date' => null, 'is_reminder' => false]);

        return redirect()->back()->with('success', 'Reminder finalized.');
    }

    /**
     * Extend a reminder's due date by 3 days.
     *
     * @return RedirectResponse
     */
    public function extendReminder(Note $note)
    {
        if ($note->reminder_date) {
            $note->update(['reminder_date' => Carbon::parse($note->reminder_date)->addDays(3)]);

            return redirect()->back()->with('success', 'Reminder extended by 3 days.');
        }

        return redirect()->back()->with('error', 'No valid reminder date to extend.');
    }

    /**
     * Delete a note from a business entity.
     *
     * @return RedirectResponse
     */
    public function destroyNote(BusinessEntity $businessEntity, Note $note)
    {
        $this->authorize('update', $businessEntity);

        // Verify the note belongs to the business entity
        if ($note->business_entity_id !== $businessEntity->id) {
            return redirect()->back()->with('error', 'Invalid note.');
        }

        $note->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Note deleted successfully.',
                'list_html' => view('business-entities.partials.notes.list', [
                    'businessEntity' => $businessEntity,
                    'notes' => $businessEntity->notes()->where('is_reminder', false)->orderByDesc('created_at')->get(),
                ])->render(),
            ]);
        }

        return redirect()->back()->withFragment('tab_notes')->with('success', 'Note deleted successfully.');
    }

    // --- Helper Methods ---

    /**
     * Helper method to determine file type based on extension.
     * Moved inside the class definition.
     *
     * @param  string  $extension  File extension.
     * @return string File type category ('image', 'document', 'spreadsheet', 'presentation', 'email', 'other').
     */
    private function normalizeEmptyGstBasisRequest(Request $request): void
    {
        if ($request->input('gst_basis') === '') {
            $request->merge(['gst_basis' => null]);
        }
    }

    private function validateTransactionGstBasis(Request $request): void
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

    private function getFileType($extension)
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $documentTypes = ['pdf', 'doc', 'docx', 'txt'];
        $spreadsheetTypes = ['xls', 'xlsx', 'csv'];
        $presentationTypes = ['ppt', 'pptx'];
        $emailTypes = ['eml', 'msg']; // Added email types

        $extension = strtolower($extension);

        if (in_array($extension, $imageTypes)) {
            return 'image';
        }
        if (in_array($extension, $documentTypes)) {
            return 'document';
        }
        if (in_array($extension, $spreadsheetTypes)) {
            return 'spreadsheet';
        }
        if (in_array($extension, $presentationTypes)) {
            return 'presentation';
        }
        if (in_array($extension, $emailTypes)) {
            return 'email';
        } // Check for email

        return 'other'; // Default category
    }

    /**
     * Determine a likely transaction type based on description keywords and amount.
     * This is a basic inference and may need refinement or user confirmation.
     *
     * @param  string  $description  Transaction description.
     * @param  float  $amount  Transaction amount.
     * @return string Key from Transaction::$transactionTypes or 'unknown'.
     */
    protected function determineTransactionType($description, $amount)
    {
        $description = strtolower($description);
        $amount = floatval($amount);

        // --- Income Rules (Amount > 0) ---
        if ($amount > 0) {
            if (preg_match('/sale|invoice|revenue|payment received/i', $description)) {
                return 'sales_revenue';
            }
            if (preg_match('/interest/i', $description)) {
                return 'interest_income';
            }
            if (preg_match('/rent received|rental income/i', $description)) {
                return 'rental_income';
            }
            if (preg_match('/grant|subsidy/i', $description)) {
                return 'grants_subsidies';
            }
            if (preg_match('/director loan|loan from director/i', $description)) {
                return 'directors_loans_to_company';
            }
            if (preg_match('/related party sale/i', $description)) {
                return 'sales_to_related_party';
            }
            // Add more income rules as needed
        }
        // --- Expense Rules (Amount < 0) ---
        elseif ($amount < 0) {
            if (preg_match('/cogs|cost of goods|inventory purchase/i', $description)) {
                return 'cogs';
            }
            if (preg_match('/wages|salary|payroll|superannuation|super fund/i', $description)) {
                return 'wages_superannuation';
            }
            if (preg_match('/rent payment|lease|electricity|water|gas|internet|phone bill|utilities/i', $description)) {
                return 'rent_utilities';
            }
            if (preg_match('/marketing|advertising|google ads|facebook ads|seo/i', $description)) {
                return 'marketing_advertising';
            }
            if (preg_match('/travel|flight|hotel|accommodation|uber|taxi/i', $description)) {
                return 'travel_expenses';
            }
            if (preg_match('/loan repayment|mortgage payment/i', $description)) {
                return 'loan_repayments';
            }
            if (preg_match('/capital purchase|asset purchase|vehicle|equipment|computer/i', $description)) {
                return 'capital_expenditure';
            }
            if (preg_match('/bas payment|gst payment|payg payment|tax office|ato/i', $description)) {
                return 'bas_payments';
            }
            if (preg_match('/director loan repayment|repay director/i', $description)) {
                return 'repayment_directors_loans';
            }
            if (preg_match('/loan to director|advance to director/i', $description)) {
                return 'company_loans_to_directors';
            } // Division 7A implication
            if (preg_match('/director fee|directors fee/i', $description)) {
                return 'directors_fees';
            }
            if (preg_match('/related party rent/i', $description)) {
                return 'rent_to_related_party';
            }
            if (preg_match('/related party purchase/i', $description)) {
                return 'purchases_from_related_party';
            }
            // Add more expense rules as needed
        }

        // Default if no rules match
        return 'unknown';
    }

    /**
     * Calculate GST amount and determine status based on amount and transaction type.
     * Assumes standard Australian GST rules (10%). Needs adjustment for specific cases.
     *
     * @param  float  $amount  The transaction amount (positive for income, negative for expense).
     * @param  string  $transactionType  The key for the transaction type.
     * @param  string  $description  Transaction description (optional, for context).
     * @return array ['gst_amount' => float, 'gst_status' => string]
     */
    protected function calculateGST($amount, $transactionType, $description)
    {
        $gstRate = 0.10; // Standard Australian GST rate
        $gstAmount = 0.0;
        // Default status: GST Free or not applicable
        $gstStatus = 'gst_free'; // Or 'not_applicable'

        // List of transaction types typically subject to GST in Australia
        // This list might need refinement based on specific business activities
        $gstApplicableTypes = [
            'sales_revenue',            // Usually taxable supply
            'rental_income',            // Commercial rent is usually taxable
            'cogs',                     // Purchases likely include GST (input credit)
            'rent_utilities',           // Expenses likely include GST (input credit)
            'marketing_advertising',    // Services likely include GST (input credit)
            'travel_expenses',          // Some elements taxable (e.g., domestic flights, hotels)
            'capital_expenditure',      // Asset purchases likely include GST (input credit)
            'directors_fees',           // Often treated as taxable supply
            'rent_to_related_party',    // Usually taxable
            'purchases_from_related_party', // Likely include GST (input credit)
            'sales_to_related_party',   // Usually taxable supply
        ];

        // List of types typically GST-free or input-taxed
        $gstFreeTypes = [
            'interest_income',          // Financial supply (input taxed)
            'grants_subsidies',         // Often GST-free, depends on conditions
            'directors_loans_to_company', // Financial supply
            'wages_superannuation',     // Outside scope of GST
            'loan_repayments',          // Principal is financial supply, interest might be
            'bas_payments',             // Tax payment, outside scope
            'repayment_directors_loans', // Financial supply
            'company_loans_to_directors', // Financial supply
        ];

        $amount = floatval($amount);

        if (in_array($transactionType, $gstApplicableTypes)) {
            // Calculate GST assuming the amount is GST-inclusive
            // GST = Total Amount * (Rate / (1 + Rate)) => Amount * (0.1 / 1.1) => Amount / 11
            $gstComponent = abs($amount) / (1 + $gstRate); // Calculate GST component
            $gstAmount = round(abs($amount) - $gstComponent, 2); // Round to 2 decimal places

            // Determine status based on income/expense
            $gstStatus = ($amount > 0) ? 'collected' : 'input_credit'; // GST collected on income, claimed on expenses

            // Refinement: Check description for explicit "GST Free" mentions?
            if (preg_match('/gst free/i', $description)) {
                $gstAmount = 0.0;
                $gstStatus = 'gst_free';
            }

        } elseif (in_array($transactionType, $gstFreeTypes)) {
            $gstAmount = 0.0;
            $gstStatus = 'gst_free'; // Explicitly GST-free or out of scope
        } else {
            // Handle 'unknown' or other types - default to GST Free unless specific rules apply
            $gstAmount = 0.0;
            $gstStatus = 'gst_free'; // Or 'check_manually'
        }

        return [
            // Return absolute value for gst_amount for consistency? Or keep sign? Convention varies.
            // Let's return positive value, status indicates direction.
            'gst_amount' => $gstAmount,
            'gst_status' => $gstStatus,
        ];
    }

    public function importPersons(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        // Logic to import persons
    }

    // Method to fetch data for compose email modal
    public function getComposeEmailData(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        $senderEmails = Email::pluck('email')->toArray();

        return response()->json([
            'senderEmails' => $senderEmails,
            // 'emailTemplates' => EmailTemplate::all(), // if you re-introduce templates
        ]);
    }

    // Method to send email
    public function sendEmail(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');
        $attachments = $request->file('attachments');
        $recipientEmail = $businessEntity->registered_email;

        try {
            // Log the email attempt
            Log::info('Attempting to send email', [
                'to' => $recipientEmail,
                'subject' => $subject,
            ]);

            // Create the email instance
            $email = new ContactEmail($subject, $message, $attachments);

            // Send the email
            Mail::to($recipientEmail)->send($email);

            // Log successful email
            Log::info('Email sent successfully', [
                'to' => $recipientEmail,
                'subject' => $subject,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully!',
            ]);

        } catch (\Exception $e) {
            // Log the detailed error
            Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'to' => $recipientEmail,
                'subject' => $subject,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display all bank accounts across all business entities for the current user.
     *
     * @return View
     */
    public function bankAccountsIndex()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $bankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->withDeleteCounts()
            ->with(['businessEntity', 'holderEntity', 'holderPerson', 'bankStatementEntries.transaction'])
            ->orderBy('account_name')
            ->get();

        $holderGroups = BankAccount::groupedByHolder($bankAccounts);

        return view('bank-accounts.index', compact('bankAccounts', 'businessEntities', 'holderGroups'));
    }

    public function createPortfolioBankAccount()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = $this->personOptionsForHolder();

        return view('bank-accounts.create', compact('businessEntities', 'persons'));
    }

    public function storePortfolioBankAccount(Request $request)
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $this->mergeBankNameFromRequest($request);

        $validated = $request->validate($this->portfolioBankAccountValidationRules());

        BankAccount::create(
            $this->portfolioBankAccountAttributesFromRequest($validated, null)
        );

        if ($request->expectsJson()) {
            return $this->bankPanelJsonResponse($request, 'Bank account added successfully!');
        }

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account added successfully!');
    }

    public function editPortfolioBankAccount(BankAccount $bankAccount)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureBankAccountOwnedByUser($bankAccount);

        $bankAccount->load(['holderEntity', 'holderPerson']);
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = $this->personOptionsForHolder();

        SecurityAuditLogger::bankAccountNumberViewed(auth()->user(), $bankAccount, 'edit_form');

        return view('bank-accounts.edit', compact('bankAccount', 'businessEntities', 'persons'));
    }

    public function updatePortfolioBankAccount(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureBankAccountOwnedByUser($bankAccount);

        $this->mergeBankNameFromRequest($request);

        $validated = $request->validate($this->portfolioBankAccountValidationRules($bankAccount));

        $bankAccount->update(
            $this->portfolioBankAccountAttributesFromRequest($validated, $bankAccount)
        );

        if ($request->expectsJson()) {
            return $this->bankPanelJsonResponse($request, 'Bank account updated successfully!');
        }

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account updated successfully!');
    }

    public function destroyPortfolioBankAccount(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureBankAccountOwnedByUser($bankAccount);

        if (! $bankAccount->isPortfolioWide()) {
            abort(403, 'Entity-scoped bank accounts must be deleted via their business entity.');
        }

        return $this->deleteBankAccountIfAllowed($bankAccount, $request);
    }

    public function destroyBankAccount(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        $this->authorize('update', $businessEntity);

        $this->ensureOperationalForAccounting($businessEntity);

        $this->ensureBankAccountAccessibleOnEntity($businessEntity, $bankAccount);

        return $this->deleteBankAccountIfAllowed($bankAccount, $request);
    }

    public function revealBankAccountNumber(Request $request, BankAccount $bankAccount)
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureBankAccountOwnedByUser($bankAccount);

        SecurityAuditLogger::bankAccountNumberViewed(
            $request->user(),
            $bankAccount,
            $request->query('context', 'reveal')
        );

        return response()->json([
            'account_number' => $bankAccount->account_number,
        ]);
    }

    /**
     * Display all transactions across all business entities for the current user.
     *
     * @return View
     */
    public function transactionsIndex()
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();

        $query = Transaction::with(['businessEntity', 'bankAccount', 'bankStatementEntries', 'asset', 'relatedEntity', 'vendor'])
            ->orderBy('date', 'desc');

        if ($entityId = request('entity_id')) {
            $query->where('business_entity_id', $entityId);
        }

        if ($type = request('type')) {
            $query->where('transaction_type', $type);
        }

        if (($ps = request('payment_status')) && in_array($ps, ['paid', 'unpaid'], true)) {
            $query->where('payment_status', $ps);
        }

        if (($dir = request('direction')) && in_array($dir, ['income', 'expense'], true)) {
            $typeKeys = $dir === 'income'
                ? array_keys(Transaction::$incomeTypes)
                : array_keys(Transaction::$expenseTypes);
            $query->whereIn('transaction_type', $typeKeys);
        }

        $transactions = $query->get();

        return view('transactions.index', compact('transactions', 'businessEntities'));
    }

    /**
     * Request fields safe for logs (no uploads, tokens, or full request dump).
     *
     * @return array<string, mixed>
     */
    private function storeTransactionRequestLogContext(Request $request, BusinessEntity $routeBusinessEntity): array
    {
        return [
            'route_business_entity_id' => $routeBusinessEntity->id,
            'request_business_entity_id' => $request->input('business_entity_id'),
            'payment_status' => $request->input('payment_status'),
            'due_date' => $request->input('due_date'),
            'paid_at' => $request->input('paid_at'),
            'transaction_type' => $request->input('transaction_type'),
            'date' => $request->input('date'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'asset_id' => $request->input('asset_id'),
            'related_entity_id' => $request->input('related_entity_id'),
            'gst_basis' => $request->input('gst_basis'),
            'has_document_upload' => $request->hasFile('document'),
            'has_payment_document' => $request->hasFile('payment_document'),
            'has_receipt_path_prefill' => $request->filled('receipt_path'),
            'user_id' => auth()->id(),
        ];
    }

    /**
     * HTML selects submit "" for the empty option; normalize so nullable|integer rules pass.
     */
    private function normalizeOptionalTransactionAssetId(Request $request): void
    {
        if ($request->has('asset_id') && $request->input('asset_id') === '') {
            $request->merge(['asset_id' => null]);
        }
    }

    /**
     * HTML selects submit "" for "no related entity"; PostgreSQL rejects '' for nullable bigint FKs.
     */
    private function normalizeOptionalRelatedEntityId(Request $request): void
    {
        if ($request->has('related_entity_id') && $request->input('related_entity_id') === '') {
            $request->merge(['related_entity_id' => null]);
        }
    }

    private function normalizeOptionalVendorId(Request $request): void
    {
        if ($request->has('vendor_id') && $request->input('vendor_id') === '') {
            $request->merge(['vendor_id' => null]);
        }
    }

    /**
     * @return array{vendor_id: ?int, vendor_name: ?string}
     */
    private function resolveTransactionVendorData(Request $request): array
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
        if ($request->input('payment_status') === 'paid' && $transactionType !== '') {
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

    private function normalizeOptionalBankAccountId(Request $request): void
    {
        $raw = $request->input('paid_by_select');
        $sel = is_string($raw) ? trim($raw) : '';

        if ($sel === '' || ! preg_match('/^be:\d+$/', $sel)) {
            $request->merge(['bank_account_id' => null]);
        } elseif (! $request->filled('bank_account_id')) {
            $request->merge(['bank_account_id' => null]);
        }
    }

    private function validatedBankAccountId(Request $request): ?int
    {
        $raw = $request->input('paid_by_select');
        $sel = is_string($raw) ? trim($raw) : '';

        if ($sel === '' || ! preg_match('/^be:(\d+)$/', $sel, $matches)) {
            return null;
        }

        $entity = BusinessEntity::query()->find((int) $matches[1]);
        if (! $entity) {
            throw ValidationException::withMessages([
                'paid_by_select' => 'Invalid entity selected.',
            ]);
        }

        $bankAccountId = $request->filled('bank_account_id')
            ? (int) $request->integer('bank_account_id')
            : null;

        $transactionType = trim((string) $request->input('transaction_type', ''));
        $direction = $transactionType !== '' ? Transaction::directionFromType($transactionType) : null;

        if ($request->input('payment_status') === 'paid'
            && $direction === 'income'
            && $bankAccountId === null) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Bank account is required when an entity is selected.',
            ]);
        }

        if ($bankAccountId === null) {
            return null;
        }

        $bankAccount = BankAccount::query()->find($bankAccountId);
        if (! $bankAccount || ! $bankAccount->canUseForTransaction($entity)) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'The selected bank account is not linked to this entity.',
            ]);
        }

        return $bankAccountId;
    }

    private function resolveBankAccountIdForTransactionSave(Request $request, ?Transaction $existing = null): ?int
    {
        $raw = $request->input('paid_by_select');
        $sel = is_string($raw) ? trim($raw) : '';

        if ($sel === '' || ! preg_match('/^be:\d+$/', $sel)) {
            return $existing?->bank_account_id;
        }

        $validated = $this->validatedBankAccountId($request);

        if ($validated !== null) {
            return $validated;
        }

        if ($existing === null) {
            return null;
        }

        $transactionType = trim((string) $request->input('transaction_type', ''));
        $direction = $transactionType !== '' ? Transaction::directionFromType($transactionType) : null;

        if ($request->input('payment_status') !== 'paid'
            || $direction !== 'expense'
            || $existing->paid_by !== $sel
            || $existing->bank_account_id === null) {
            return null;
        }

        $entity = BusinessEntity::query()->find((int) substr($sel, 3));
        $account = BankAccount::query()->find($existing->bank_account_id);

        if ($entity && $account && $account->canUseForTransaction($entity)) {
            return $existing->bank_account_id;
        }

        return null;
    }

    private function bankAccountWorkspaceJsonResponse(BusinessEntity $businessEntity, string $message): JsonResponse
    {
        $entityBankAccountLinks = $businessEntity->bankAccountLinksForDisplay();
        $entityBankAccountGroups = $this->entityBankAccountHolderGroups($businessEntity, $entityBankAccountLinks);

        return response()->json([
            'status' => true,
            'message' => $message,
            'list_html' => view('business-entities.partials.bank-accounts.list', [
                'businessEntity' => $businessEntity,
                'holderGroups' => $entityBankAccountGroups,
            ])->render(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BusinessEntityBankAccount>  $links
     * @return array<int, array<string, mixed>>
     */
    private function entityBankAccountHolderGroups(BusinessEntity $businessEntity, $links): array
    {
        $groups = BankAccount::groupedLinksByHolder($links, $businessEntity->id);

        return $this->bankAccountAssetLinkService->enrichHolderGroupsWithRentAssets($businessEntity, $groups);
    }

    /**
     * Hidden rent-asset pickers can still submit stale IDs when purpose is not rent receiving.
     */
    private function forgetRentCollectionAssetIdsUnlessRentReceiving(Request $request): void
    {
        if ($request->input('account_purpose') !== BankAccount::PURPOSE_RENT_RECEIVING) {
            $request->merge([
                'rent_collection_asset_ids' => null,
            ]);
        }
    }

    private function redirectToBusinessEntityShow(
        BusinessEntity $businessEntity,
        ?int $bankAccountId,
        string $fragment
    ): RedirectResponse {
        $params = ['business_entity' => $businessEntity->id];
        if ($bankAccountId !== null) {
            $params['bank_account_id'] = $bankAccountId;
        }

        return redirect()->route('business-entities.show', $params)->withFragment($fragment);
    }

    /**
     * Only allow pre-filled S3 keys that belong to this entity (prevents cross-tenant path injection).
     */
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

        $existingExt = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
        if ($existingExt !== '' && $existingExt === $ext) {
            return $base;
        }

        return "{$base}.{$ext}";
    }

    /**
     * Receipt documents are scoped to entity or a specific asset; changing asset breaks the link.
     */
    private function detachIncompatibleReceiptDocument(Transaction $transaction, ?int $newAssetId): void
    {
        if (! $transaction->document_id) {
            return;
        }

        $doc = Document::query()->find($transaction->document_id);
        if (! $doc) {
            $transaction->forceFill(['document_id' => null, 'receipt_path' => null])->save();

            return;
        }

        $docAsset = $doc->asset_id !== null ? (int) $doc->asset_id : null;
        $new = $newAssetId !== null ? (int) $newAssetId : null;

        if ($docAsset !== $new) {
            $transaction->forceFill(['document_id' => null, 'receipt_path' => null])->save();
        }
    }

    private function mergeBankNameFromRequest(Request $request): void
    {
        $request->merge([
            'bank_name' => BankAccount::resolveBankNameFromFormInput(
                $request->input('bank_name_select'),
                $request->input('bank_name_other'),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bankNameFieldValidationRules(): array
    {
        return [
            'bank_name_select' => ['nullable', 'string', 'max:255'],
            'bank_name_other'  => ['nullable', 'string', 'max:255'],
            'bank_name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === BankAccount::BANK_OTHER) {
                        $fail('Please enter a bank name.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entityBankAccountValidationRules(): array
    {
        return array_merge([
            'account_name' => 'required|string|max:255',
        ], $this->bankNameFieldValidationRules(), [
            'bsb' => ['required', 'string', 'max:10', function ($attribute, $value, $fail) {
                $normalized = BankAccount::normalizeBsb($value);
                if ($normalized === null || strlen($normalized) !== 6) {
                    $fail('The BSB must be 6 digits (with or without a hyphen).');
                }
            }],
            'account_number' => 'required|string|max:255',
            'account_purpose' => ['required', Rule::in(BankAccount::ENTITY_PURPOSES)],
        ], $this->holderValidationRules());
    }

    /**
     * @return array<string, mixed>
     */
    private function portfolioBankAccountValidationRules(?BankAccount $existing = null): array
    {
        return array_merge([
            'account_name' => 'required|string|max:255',
        ], $this->bankNameFieldValidationRules(), [
            'bsb' => ['required', 'string', 'max:10', function ($attribute, $value, $fail) {
                $normalized = BankAccount::normalizeBsb($value);
                if ($normalized === null || strlen($normalized) !== 6) {
                    $fail('The BSB must be 6 digits (with or without a hyphen).');
                }
            }],
            'account_number' => 'required|string|max:255',
            'account_purpose' => ['required', Rule::in(BankAccount::PURPOSES)],
            'business_entity_id' => [
                Rule::requiredIf(fn () => ! in_array(request('account_purpose'), [
                    BankAccount::PURPOSE_GENERAL,
                    BankAccount::PURPOSE_LOAN_REPAYMENT,
                ], true)),
                'nullable',
                BusinessEntity::ruleExistsOperational(),
            ],
        ], $this->holderValidationRules());
    }

    /**
     * Shared validation rules for the Account Holder section on bank account forms.
     *
     * @return array<string, mixed>
     */
    private function holderValidationRules(): array
    {
        return [
            'holder_type'      => ['required', Rule::in(BankAccount::HOLDER_TYPES)],
            'holder_entity_id' => [
                'nullable',
                Rule::requiredIf(fn () => request('holder_type') === BankAccount::HOLDER_ENTITY),
                BusinessEntity::ruleExistsOperational(),
            ],
            'holder_person_id' => [
                'nullable',
                Rule::requiredIf(fn () => request('holder_type') === BankAccount::HOLDER_PERSON),
                Rule::exists('persons', 'id')->where(function ($query) {
                    $query->whereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('entity_person')
                            ->join('business_entities', 'business_entities.id', '=', 'entity_person.business_entity_id')
                            ->whereColumn('entity_person.person_id', 'persons.id')
                            ->where('business_entities.exclude_from_financial_reports', false);
                    });
                }),
            ],
            'holder_other'     => [
                'nullable',
                Rule::requiredIf(fn () => request('holder_type') === BankAccount::HOLDER_OTHER),
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function bankAccountAttributesFromRequest(array $validated, BusinessEntity $businessEntity): array
    {
        return array_merge([
            'business_entity_id' => $businessEntity->id,
            'user_id'            => $businessEntity->user_id ?? auth()->id(),
            'account_name'       => $validated['account_name'],
            'bank_name'          => $validated['bank_name'],
            'bsb'                => BankAccount::normalizeBsb($validated['bsb']),
            'account_number'     => $validated['account_number'],
            'account_purpose'    => $validated['account_purpose'],
        ], $this->holderAttributesFromValidated($validated));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function portfolioBankAccountAttributesFromRequest(array $validated, ?BankAccount $existing = null): array
    {
        $purpose  = $validated['account_purpose'];
        $entityId = $purpose === BankAccount::PURPOSE_LOAN_REPAYMENT
            ? null
            : (isset($validated['business_entity_id']) ? (int) $validated['business_entity_id'] : null);

        if ($entityId) {
            BusinessEntity::query()
                ->operationalEntities()
                ->whereKey($entityId)
                ->firstOrFail();
        }

        return array_merge([
            'business_entity_id' => $entityId,
            // Portfolio accounts are created by the logged-in user; entity ownership may differ.
            'user_id'            => $existing?->user_id ?? (int) auth()->id(),
            'account_name'       => $validated['account_name'],
            'bank_name'          => $validated['bank_name'],
            'bsb'                => BankAccount::normalizeBsb($validated['bsb']),
            'account_number'     => $validated['account_number'],
            'account_purpose'    => $purpose,
        ], $this->holderAttributesFromValidated($validated));
    }

    /**
     * Extract and normalise holder fields from validated data.
     * Only the fields relevant to the chosen holder_type are kept non-null.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function holderAttributesFromValidated(array $validated): array
    {
        $type = $validated['holder_type'] ?? null;

        return [
            'holder_type'      => $type,
            'holder_entity_id' => $type === BankAccount::HOLDER_ENTITY
                ? (isset($validated['holder_entity_id']) ? (int) $validated['holder_entity_id'] : null)
                : null,
            'holder_person_id' => $type === BankAccount::HOLDER_PERSON
                ? (isset($validated['holder_person_id']) ? (int) $validated['holder_person_id'] : null)
                : null,
            'holder_other'     => $type === BankAccount::HOLDER_OTHER
                ? ($validated['holder_other'] ?? null)
                : null,
        ];
    }

    /**
     * Persons linked to any of the authenticated user's entities, suitable for the holder picker.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Person>
     */
    private function personOptionsForHolder(): \Illuminate\Support\Collection
    {
        return Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $person) => $person->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function deleteBankAccountIfAllowed(BankAccount $bankAccount, ?Request $request = null): RedirectResponse|JsonResponse
    {
        $bankAccount->loadCount(['transactions', 'bankStatementEntries', 'assets']);

        if (! $bankAccount->canBeDeleted()) {
            if ($request?->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $bankAccount->deleteBlockedReason(),
                ], 422);
            }

            return redirect()->back()->with('error', $bankAccount->deleteBlockedReason());
        }

        $bankAccount->delete();

        if ($request?->expectsJson()) {
            return $this->bankPanelJsonResponse($request, 'Bank account deleted successfully.');
        }

        return redirect()->back()->with('success', 'Bank account deleted successfully.');
    }

    private function bankPanelJsonResponse(Request $request, string $message): JsonResponse
    {
        $context = $request->input('_bank_list_context');

        if ($context === null && $request->integer('_person_workspace_id')) {
            $context = 'person:'.$request->integer('_person_workspace_id');
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'list_html' => BankAccountPanelController::listHtmlForContext($context),
        ]);
    }

    private function ensureBankAccountOwnedByUser(BankAccount $bankAccount): void
    {
        if ($bankAccount->isAccessibleByCurrentUser()) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }

    private function ensureBankAccountAccessibleOnEntity(BusinessEntity $businessEntity, BankAccount $bankAccount): void
    {
        if ($bankAccount->hasLinkOnEntity($businessEntity)) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}

