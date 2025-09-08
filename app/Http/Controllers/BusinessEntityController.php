<?php

namespace App\Http\Controllers;

// Import necessary models and classes
use App\Models\BusinessEntity;
use App\Models\Asset;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon; // Added for date manipulation
use Illuminate\Support\Facades\Log; // Added for logging
use Illuminate\Support\Facades\Storage; // Added for file storage
use Illuminate\Validation\ValidationException; // Added for handling validation exceptions
use App\Models\Reminder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // Add this at the top with other use statements

use App\Models\EmailTemplate;
use App\Models\Email;
use App\Mail\ContactEmail;
use Illuminate\Support\Facades\Mail;

class BusinessEntityController extends Controller
{

    /**
     * Display a listing of the business entities.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Retrieve all business entities with their associated persons
        $businessEntities = BusinessEntity::with('persons')->get();
        // Retrieve upcoming reminders
        $reminders = Note::where('is_reminder', true)
            ->whereDate('reminder_date', '>=', now()->toDateString())
            ->orderBy('reminder_date')
            ->get();

        // Return the index view with the retrieved data
        return view('business-entities.index', compact('businessEntities', 'reminders'));
    }

    /**
     * Show the form for creating a new business entity.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Get all persons and business entities for appointor selection
        $persons = \App\Models\Person::all();
        $businessEntities = BusinessEntity::where('entity_type', '!=', 'Trust')->get(); // Exclude trusts to prevent circular references
        
        return view('business-entities.create', compact('persons', 'businessEntities'));
    }

    /**
     * Store a newly created business entity in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'legal_name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'abn' => 'nullable|string|max:11|unique:business_entities,abn',
            'acn' => 'nullable|string|max:9|unique:business_entities,acn',
            'tfn' => 'nullable|string|max:9', // Consider security implications of storing TFN
            'corporate_key' => 'nullable|string|max:255',
            'registered_address' => 'required|string',
            'registered_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:15',
            'asic_renewal_date' => 'nullable|date',
            
            // Trust-specific validation
            'trust_type' => 'required_if:entity_type,Trust|in:Discretionary,Unit,Fixed,Testamentary,Charitable',
            'trust_establishment_date' => 'required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_date' => 'required_if:entity_type,Trust|date|before_or_equal:today',
            'trust_deed_reference' => 'nullable|string|max:255',
            'trust_vesting_date' => 'nullable|date|after:trust_establishment_date',
            'trust_vesting_conditions' => 'nullable|string|max:1000',
            'appointor_type' => 'required_if:entity_type,Trust|in:person,entity',
            'appointor_person_id' => 'required_if:appointor_type,person|exists:persons,id',
            'appointor_entity_id' => 'required_if:appointor_type,entity|exists:business_entities,id',
        ], [
            'trust_type.required_if' => 'Trust type is required when entity type is Trust.',
            'trust_establishment_date.required_if' => 'Trust establishment date is required when entity type is Trust.',
            'trust_deed_date.required_if' => 'Trust deed date is required when entity type is Trust.',
            'appointor_type.required_if' => 'Appointor type is required when entity type is Trust.',
            'appointor_person_id.required_if' => 'Please select an appointor person.',
            'appointor_entity_id.required_if' => 'Please select an appointor entity.',
        ]);

        // Create the business entity with validated data
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
            'user_id' => auth()->id(), // Associate with the logged-in user
            'status' => 'Active', // Default status
        ]);

        // Redirect to the index page with a success message
        return redirect()->route('business-entities.index')->with('success', 'Business entity added successfully!');
    }

    /**
     * Display the specified business entity.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\View\View
     */
    public function show(BusinessEntity $businessEntity)
    {
        $assets = $businessEntity->assets;
        $persons = $businessEntity->persons()->with(['person', 'trusteeEntity'])->get();
        $bankAccounts = $businessEntity->bankAccounts()->with(['bankStatementEntries.transaction'])->get();
        $transactions = $businessEntity->transactions()->with(['bankStatementEntries'])->orderBy('date', 'desc')->get();
        $documents = $businessEntity->documents;
        $notes = $businessEntity->notes()->where('is_reminder', false)->orderBy('created_at', 'desc')->get();
        
        // Get reminders using the new Reminder model
        $reminders = Reminder::where('business_entity_id', $businessEntity->id)
            ->with(['user'])
            ->orderBy('next_due_date')
            ->get();

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
            'transactions',
            'documents',
            'notes',
            'reminders',
            'unmatchedTransactions',
            'contactLists'
        ));
    }

    /**
     * Display the main dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();
        $businessEntities = BusinessEntity::where('user_id', $user->id)->get();
        $assets = Asset::whereHas('businessEntity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        
        // Fetch Reminder records
        $reminders = Reminder::where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('businessEntity', function($q) use ($user) {
                  $q->where('user_id', $user->id);
              })
              ->orWhereHas('asset.businessEntity', function($q) use ($user) {
                  $q->where('user_id', $user->id);
              });
        })
        ->active()
        ->dueWithinDays(15)
        ->with(['businessEntity', 'asset', 'user'])
        ->orderBy('next_due_date')
        ->get();

        // Fetch Note-based reminders
        $noteReminders = Note::where('is_reminder', true)
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('businessEntity', function($q) use ($user) {
                      $q->where('user_id', $user->id);
                  })
                  ->orWhereHas('asset.businessEntity', function($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
            })
            ->whereDate('reminder_date', '>=', now())
            ->whereDate('reminder_date', '<=', now()->addDays(15))
            ->with(['businessEntity', 'asset', 'user'])
            ->orderBy('reminder_date')
            ->get()
            ->map(function($note) {
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

        // Combine reminders, sort by due date
        $allReminders = $reminders->concat($noteReminders)->sortBy('next_due_date');

        $persons = EntityPerson::whereHas('businessEntity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['person', 'trusteeEntity', 'businessEntity'])->get();

        // Group persons by their actual Person record to avoid duplicates
        $uniquePersons = $persons->where('person_id', '!=', null)
            ->groupBy('person_id')
            ->map(function($entityPersonGroup) {
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

        $assetDueDates = Asset::whereHas('businessEntity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where(function($q) {
            $q->whereDate('registration_due_date', '>=', now())
              ->whereDate('registration_due_date', '<=', now()->addDays(15));
        })->with('businessEntity')->get();

        $entityDueDates = EntityPerson::whereHas('businessEntity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where(function($q) {
            $q->whereDate('asic_due_date', '>=', now())
              ->whereDate('asic_due_date', '<=', now()->addDays(15));
        })->with('businessEntity')->get();

        return view('dashboard', compact(
            'businessEntities',
            'assets',
            'allReminders', // Pass combined reminders
            'persons',
            'uniquePersons',
            'assetDueDates',
            'entityDueDates'
        ));
    }

    /**
     * Extract transaction information from an uploaded document using OpenAI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */


    /**
     * Store a new transaction for a business entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTransaction(Request $request, BusinessEntity $businessEntity)
    {
        // Validate the transaction data
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:' . implode(',', array_keys(Transaction::$transactionTypes)),
            'related_entity_id' => 'nullable|exists:business_entities,id',
            'gst_amount' => 'nullable|numeric',
            'gst_status' => 'nullable|in:included,excluded,gst_free,collected,input_credit', // Added more specific statuses
            'document' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', // Optional document upload
            'document_name' => 'nullable|string|max:255',
            // 'business_entity_id' is implicitly available via the route model binding ($businessEntity)
        ]);

        $receiptPath = $request->input('receipt_path'); // Get path if pre-filled from extraction

        // Handle file upload if a new document is provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $customName = $request->document_name ?: pathinfo($originalName, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $uploadDate = now()->format('Y-m-d');
            $fileName = "{$customName}_{$uploadDate}.{$extension}";
            $folderPath = "Receipts/{$businessEntity->id}_{$businessEntity->legal_name}";
            $s3Path = "{$folderPath}/{$fileName}";

            // Upload to S3 (S3 doesn't require explicit directory creation)
            Storage::disk('s3')->put($s3Path, file_get_contents($file->getRealPath()));
            $receiptPath = $s3Path; // Update receipt path with the newly uploaded file
        }

        // Create the transaction record
        $transaction = Transaction::create([
            'business_entity_id' => $businessEntity->id,
            'related_entity_id' => $request->related_entity_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_type' => $request->transaction_type,
            'gst_amount' => $request->gst_amount,
            'gst_status' => $request->gst_status,
            'receipt_path' => $receiptPath,
            // 'bank_account_id' might be set later during reconciliation
        ]);

        // Redirect to dashboard (or entity show page) with success message
        // Consider redirecting to business-entities.show instead?
        return redirect()->route('dashboard')->with('success', "Transaction '{$transaction->description}' added successfully!");
    }

    /**
     * Store a new transaction for a bank account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBankTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        // Authorization check
        if ($bankAccount->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized');
        }

        // Validate the transaction data
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:' . implode(',', array_keys(Transaction::$transactionTypes)),
            'related_entity_id' => 'nullable|exists:business_entities,id',
            'gst_amount' => 'nullable|numeric',
            'gst_status' => 'nullable|in:included,excluded,gst_free,collected,input_credit',
            'document' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'document_name' => 'nullable|string|max:255',
        ]);

        $receiptPath = $request->input('receipt_path');

        // Handle file upload if a new document is provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $customName = $request->document_name ?: pathinfo($originalName, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $uploadDate = now()->format('Y-m-d');
            $fileName = "{$customName}_{$uploadDate}.{$extension}";
            $folderPath = "Receipts/{$businessEntity->id}_{$businessEntity->legal_name}";
            $s3Path = "{$folderPath}/{$fileName}";

            Storage::disk('s3')->put($s3Path, file_get_contents($file->getRealPath()));
            $receiptPath = $s3Path;
        }

        // Create the transaction record
        $transaction = Transaction::create([
            'business_entity_id' => $businessEntity->id,
            'related_entity_id' => $request->related_entity_id,
            'bank_account_id' => $bankAccount->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_type' => $request->transaction_type,
            'gst_amount' => $request->gst_amount,
            'gst_status' => $request->gst_status,
            'receipt_path' => $receiptPath,
        ]);

        // Redirect to the bank account show page with success message
        return redirect()->route('business-entities.show', [$businessEntity->id, 'bank_account_id' => $bankAccount->id, '#tab_bank_accounts'])
            ->with('success', "Transaction '{$transaction->description}' added successfully!");
    }

    /**
     * Show the form for editing the specified transaction.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editTransaction(BusinessEntity $businessEntity, Transaction $transaction)
    {
        // Authorization check: ensure the transaction belongs to the business entity
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }
        // Return the edit view with necessary data
        return view('business-entities.transactions.edit', compact('businessEntity', 'transaction'));
    }

    /**
     * Update the specified transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTransaction(Request $request, BusinessEntity $businessEntity, Transaction $transaction)
    {
        // Authorization check
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the updated data
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:' . implode(',', array_keys(Transaction::$transactionTypes)),
            'related_entity_id' => 'nullable|exists:business_entities,id',
            'gst_amount' => 'nullable|numeric',
            'gst_status' => 'nullable|in:included,excluded,gst_free,collected,input_credit',
            // Note: Updating receipt_path might require additional logic/validation if allowed
        ]);

        // Update the transaction with validated fields
        $transaction->update($request->only([
            'date', 'amount', 'description', 'transaction_type', 'related_entity_id', 'gst_amount', 'gst_status'
        ]));

        // Redirect to the business entity show page with success message
        return redirect()->route('business-entities.show', $businessEntity->id)->with('success', 'Transaction updated successfully!');
    }

    /**
     * Update the specified bank account transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBankTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount, Transaction $transaction)
    {
        // Authorization checks
        if ($transaction->business_entity_id !== $businessEntity->id || $transaction->bank_account_id !== $bankAccount->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the updated data
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:' . implode(',', array_keys(Transaction::$transactionTypes)),
            'related_entity_id' => 'nullable|exists:business_entities,id',
            'gst_amount' => 'nullable|numeric',
            'gst_status' => 'nullable|in:included,excluded,gst_free,collected,input_credit',
        ]);

        // Update the transaction with validated fields
        $transaction->update($request->only([
            'date', 'amount', 'description', 'transaction_type', 'related_entity_id', 'gst_amount', 'gst_status'
        ]));

        // Redirect to the bank account show page with success message
        return redirect()->route('business-entities.show', [$businessEntity->id, 'bank_account_id' => $bankAccount->id, '#tab_bank_accounts'])
            ->with('success', 'Transaction updated successfully!');
    }

    /**
     * Match a transaction to a bank statement entry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function matchTransaction(Request $request, BusinessEntity $businessEntity, Transaction $transaction)
    {
        // Authorization check
        if ($transaction->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the bank statement entry ID
        $request->validate([
            'bank_statement_entry_id' => 'required|exists:bank_statement_entries,id',
        ]);

        // Find the bank statement entry
        $entry = BankStatementEntry::findOrFail($request->bank_statement_entry_id);

        // Further authorization: ensure the bank statement entry belongs to the same business entity
        if ($entry->bankAccount->business_entity_id !== $businessEntity->id) {
             abort(403, 'Bank statement entry does not belong to this business entity.');
        }

        // Update the bank statement entry to link it to the transaction
        $entry->update(['transaction_id' => $transaction->id]);
        // Optionally, update the transaction's bank_account_id if it's null
        if(is_null($transaction->bank_account_id)){
            $transaction->update(['bank_account_id' => $entry->bank_account_id]);
        }


        // Redirect back to the entity show page (likely to the bank accounts tab)
        return redirect()->route('business-entities.show', [$businessEntity->id, '#tab_bank_accounts'])->with('success', 'Transaction matched successfully!');
    }

    /**
     * Get bank accounts for a specific business entity (useful for AJAX calls).
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankAccounts(BusinessEntity $businessEntity)
    {
        // Return a JSON response containing bank account IDs and names/nicknames
        return response()->json($businessEntity->bankAccounts()->select('id', 'bank_name', 'nickname')->get());
    }

    /**
     * Upload a general document for a business entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadDocument(Request $request, BusinessEntity $businessEntity)
    {
        // Validate the uploaded file and optional custom name
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,svg,webp,xls,xlsx,csv,ppt,pptx,eml,msg|max:10240', // 10MB max size, added email types
            'file_name' => 'nullable|string|max:255'
        ]);

        try {
            $file = $request->file('document');

            // Determine filename (custom or generated)
            $fileName = $request->file_name
                ? $request->file_name . '.' . $file->getClientOriginalExtension()
                : time() . '_' . $file->getClientOriginalName(); // Use timestamp prefix for uniqueness

            // Define S3 path structure
            $folderPath = "GeneralInfo/{$businessEntity->legal_name}/docs"; // Consistent path structure
            $s3Path = "{$folderPath}/{$fileName}";

            // Upload file to S3
            Storage::disk('s3')->put($s3Path, file_get_contents($file->getRealPath()));

            // Generate a temporary URL for immediate access (expires in 1 hour)
            $fileUrl = Storage::disk('s3')->temporaryUrl($s3Path, now()->addHour());

            // Redirect back with success message and file URL
            return redirect()->route('business-entities.show', $businessEntity->id)
                ->with('success', 'Document uploaded successfully!')
                ->with('file_url', $fileUrl);

        } catch (\Exception $e) {
            Log::error("Failed to upload document to S3 for entity {$businessEntity->id}: " . $e->getMessage());
            // Redirect back with a generic error message
            return redirect()->route('business-entities.show', $businessEntity->id)
               ->with('error', 'Failed to upload document. Please try again.');
        }
    }

    /**
     * Store a new note for a business entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeNote(Request $request, BusinessEntity $businessEntity)
    {
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
             // Log validation errors for debugging
             Log::error('Note validation failed: ', $e->errors());
             // Redirect back with validation errors
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
            Log::error('Failed to save note: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save note. Please try again.')->withInput();
        }

        // Redirect back with success message
        return redirect()->back()->with('success', 'Note added successfully!');
    }

    /**
     * Show the form for editing the specified business entity.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\View\View
     */
    public function edit(BusinessEntity $businessEntity)
    {
        // Return the edit view, passing the business entity data
        return view('business-entities.edit', compact('businessEntity'));
    }

    /**
     * Update the specified business entity in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BusinessEntity $businessEntity)
    {
        // Validate the incoming request data, ensuring uniqueness checks ignore the current entity
        $request->validate([
            'legal_name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'entity_type' => 'required|in:Sole Trader,Company,Trust,Partnership',
            'abn' => 'nullable|string|max:11|unique:business_entities,abn,' . $businessEntity->id,
            'acn' => 'nullable|string|max:9|unique:business_entities,acn,' . $businessEntity->id,
            'tfn' => 'nullable|string|max:9', // Consider security
            'corporate_key' => 'nullable|string|max:255',
            'registered_address' => 'required|string',
            'registered_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:15',
            'asic_renewal_date' => 'nullable|date',
            'status' => 'required|in:Active,Inactive,Archived', // Added status update
        ]);

        // Update the business entity with validated data
        $businessEntity->update([
            'legal_name' => $request->legal_name,
            'trading_name' => $request->trading_name,
            'entity_type' => $request->entity_type,
            'abn' => $request->abn,
            'acn' => $request->acn,
            'tfn' => $request->tfn, // Ensure proper encryption/security
            'corporate_key' => $request->corporate_key,
            'registered_address' => $request->registered_address,
            'registered_email' => $request->registered_email,
            'phone_number' => $request->phone_number,
            'asic_renewal_date' => $request->asic_renewal_date,
            'status' => $request->status, // Update status
        ]);

        // Redirect to the show page for the updated entity with success message
        return redirect()->route('business-entities.show', $businessEntity->id)->with('success', 'Business entity updated successfully!');
    }

    // --- Bank Account Methods ---

    /**
     * Show the form for creating a new bank account for a business entity.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\View\View
     */
    public function createBankAccount(BusinessEntity $businessEntity)
    {
        // Return the view for creating a bank account, passing the parent entity
        return view('business-entities.bank-accounts.create', compact('businessEntity'));
    }

    /**
     * Store a newly created bank account for a business entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBankAccount(Request $request, BusinessEntity $businessEntity)
    {
        // Validate bank account details
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bsb' => 'required|string|size:6', // Australian BSB format
            'account_number' => 'required|string|max:255', // Varies by bank
            'nickname' => 'nullable|string|max:255', // Optional friendly name
        ]);

        // Create the bank account associated with the business entity
        $businessEntity->bankAccounts()->create([
            'bank_name' => $request->bank_name,
            'bsb' => $request->bsb,
            'account_number' => $request->account_number,
            'nickname' => $request->nickname,
        ]);

        // Redirect back to the entity show page (likely bank accounts tab) with success message
        return redirect()->route('business-entities.show', [$businessEntity->id, '#tab_bank_accounts'])
            ->with('success', 'Bank account added successfully!');
    }

    /**
     * Show the form for editing the specified bank account.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editBankAccount(BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        // Authorization: Ensure the bank account belongs to the specified business entity
        if ($bankAccount->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }
        // Return the edit view, passing entity and bank account data
        return view('business-entities.bank-accounts.edit', compact('businessEntity', 'bankAccount'));
    }

    /**
     * Update the specified bank account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBankAccount(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        // Authorization check
        if ($bankAccount->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the updated bank account details
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'bsb' => 'required|string|size:6',
            'account_number' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
        ]);

        // Update the bank account record
        $bankAccount->update([
            'bank_name' => $request->bank_name,
            'bsb' => $request->bsb,
            'account_number' => $request->account_number,
            'nickname' => $request->nickname,
        ]);

        // Redirect back to the entity show page (bank accounts tab) with success message
        return redirect()->route('business-entities.show', [$businessEntity->id, '#tab_bank_accounts'])
            ->with('success', 'Bank account updated successfully!');
    }





    /**
     * Allocate a bank statement entry to an existing transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\BankStatementEntry  $bankStatementEntry
     * @return \Illuminate\Http\RedirectResponse
     */
    public function allocateTransaction(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount, BankStatementEntry $bankStatementEntry)
    {
        // Authorization checks
        if ($bankStatementEntry->bank_account_id !== $bankAccount->id || $bankAccount->business_entity_id !== $businessEntity->id) {
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
            if (!$transaction || $transaction->business_entity_id !== $businessEntity->id) {
                 return redirect()->route('business-entities.show', [$businessEntity->id, '#tab_bank_accounts'])
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
        return redirect()->route('business-entities.show', [$businessEntity->id, '#tab_bank_accounts'])
            ->with('success', $message);
    }


    /**
     * Show the form for creating a new transaction, potentially pre-filled from receipt extraction.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function createTransaction(BusinessEntity $businessEntity, BankAccount $bankAccount)
    {
        // Authorization check
        if ($bankAccount->business_entity_id !== $businessEntity->id) {
            abort(403, 'Unauthorized');
        }

        // Get available transaction types and business entities (if needed for transfers etc.)
        $transactionTypes = Transaction::$transactionTypes;
        $businessEntities = BusinessEntity::all(); // Or maybe just the current one?

        // Retrieve pre-filled data from session if redirected from receipt extraction
        $transactionData = session('transactionData', [
            'date' => now()->toDateString(),
            'amount' => '',
            'description' => '',
            'transaction_type' => '',
            'gst_amount' => '',
            'gst_status' => '',
            'receipt_path' => '',
        ]);

        // Return the create transaction view
        return view('business-entities.bank-accounts.transactions.create', compact(
            'businessEntity', 'bankAccount', 'transactionTypes', 'businessEntities', 'transactionData'
        ));
    }



    /**
     * Display the specified transaction.
     *
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showTransaction(BusinessEntity $businessEntity, BankAccount $bankAccount, Transaction $transaction)
    {
        // Authorization checks
        if ($transaction->bank_account_id !== $bankAccount->id || $bankAccount->business_entity_id !== $businessEntity->id) {
            abort(404); // Or abort(403) if preferred
        }
        // Return the transaction show view
        return view('transactions.show', compact('businessEntity', 'bankAccount', 'transaction'));
    }

    /**
     * Finalize a reminder by removing its reminder status.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\RedirectResponse
     */
    public function finalizeReminder(Note $note)
    {
        $note->update(['reminder_date' => null, 'is_reminder' => false]);
        return redirect()->back()->with('success', 'Reminder finalized.');
    }

    /**
     * Extend a reminder's due date by 3 days.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\RedirectResponse
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
     * @param  \App\Models\BusinessEntity  $businessEntity
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyNote(BusinessEntity $businessEntity, Note $note)
    {
        // Verify the note belongs to the business entity
        if ($note->business_entity_id !== $businessEntity->id) {
            return redirect()->back()->with('error', 'Invalid note.');
        }

        $note->delete();
        return redirect()->back()->with('success', 'Note deleted successfully.');
    }

    // --- Helper Methods ---

    /**
     * Helper method to determine file type based on extension.
     * Moved inside the class definition.
     *
     * @param string $extension File extension.
     * @return string File type category ('image', 'document', 'spreadsheet', 'presentation', 'email', 'other').
     */
    private function getFileType($extension)
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $documentTypes = ['pdf', 'doc', 'docx', 'txt'];
        $spreadsheetTypes = ['xls', 'xlsx', 'csv'];
        $presentationTypes = ['ppt', 'pptx'];
        $emailTypes = ['eml', 'msg']; // Added email types

        $extension = strtolower($extension);

        if (in_array($extension, $imageTypes)) return 'image';
        if (in_array($extension, $documentTypes)) return 'document';
        if (in_array($extension, $spreadsheetTypes)) return 'spreadsheet';
        if (in_array($extension, $presentationTypes)) return 'presentation';
        if (in_array($extension, $emailTypes)) return 'email'; // Check for email
        return 'other'; // Default category
    }

    /**
     * Determine a likely transaction type based on description keywords and amount.
     * This is a basic inference and may need refinement or user confirmation.
     *
     * @param string $description Transaction description.
     * @param float $amount Transaction amount.
     * @return string Key from Transaction::$transactionTypes or 'unknown'.
     */
    protected function determineTransactionType($description, $amount)
    {
        $description = strtolower($description);
        $amount = floatval($amount);

        // --- Income Rules (Amount > 0) ---
        if ($amount > 0) {
            if (preg_match('/sale|invoice|revenue|payment received/i', $description)) return 'sales_revenue';
            if (preg_match('/interest/i', $description)) return 'interest_income';
            if (preg_match('/rent received|rental income/i', $description)) return 'rental_income';
            if (preg_match('/grant|subsidy/i', $description)) return 'grants_subsidies';
            if (preg_match('/director loan|loan from director/i', $description)) return 'directors_loans_to_company';
            if (preg_match('/related party sale/i', $description)) return 'sales_to_related_party';
            // Add more income rules as needed
        }
        // --- Expense Rules (Amount < 0) ---
        elseif ($amount < 0) {
            if (preg_match('/cogs|cost of goods|inventory purchase/i', $description)) return 'cogs';
            if (preg_match('/wages|salary|payroll|superannuation|super fund/i', $description)) return 'wages_superannuation';
            if (preg_match('/rent payment|lease|electricity|water|gas|internet|phone bill|utilities/i', $description)) return 'rent_utilities';
            if (preg_match('/marketing|advertising|google ads|facebook ads|seo/i', $description)) return 'marketing_advertising';
            if (preg_match('/travel|flight|hotel|accommodation|uber|taxi/i', $description)) return 'travel_expenses';
            if (preg_match('/loan repayment|mortgage payment/i', $description)) return 'loan_repayments';
            if (preg_match('/capital purchase|asset purchase|vehicle|equipment|computer/i', $description)) return 'capital_expenditure';
            if (preg_match('/bas payment|gst payment|payg payment|tax office|ato/i', $description)) return 'bas_payments';
            if (preg_match('/director loan repayment|repay director/i', $description)) return 'repayment_directors_loans';
            if (preg_match('/loan to director|advance to director/i', $description)) return 'company_loans_to_directors'; // Division 7A implication
            if (preg_match('/director fee|directors fee/i', $description)) return 'directors_fees';
            if (preg_match('/related party rent/i', $description)) return 'rent_to_related_party';
            if (preg_match('/related party purchase/i', $description)) return 'purchases_from_related_party';
            // Add more expense rules as needed
        }

        // Default if no rules match
        return 'unknown';
    }

    /**
     * Calculate GST amount and determine status based on amount and transaction type.
     * Assumes standard Australian GST rules (10%). Needs adjustment for specific cases.
     *
     * @param float $amount The transaction amount (positive for income, negative for expense).
     * @param string $transactionType The key for the transaction type.
     * @param string $description Transaction description (optional, for context).
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
            'repayment_directors_loans',// Financial supply
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
        // Logic to import persons
    }

    // Method to fetch data for compose email modal
    public function getComposeEmailData(BusinessEntity $businessEntity)
    {
        $senderEmails = Email::pluck('email')->toArray();

        return response()->json([
            'senderEmails' => $senderEmails,
            // 'emailTemplates' => EmailTemplate::all(), // if you re-introduce templates
        ]);
    }

    // Method to send email
    public function sendEmail(Request $request, BusinessEntity $businessEntity)
    {
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
                'subject' => $subject
            ]);

            // Create the email instance
            $email = new ContactEmail($subject, $message, $attachments);
            
            // Send the email
            Mail::to($recipientEmail)->send($email);

            // Log successful email
            Log::info('Email sent successfully', [
                'to' => $recipientEmail,
                'subject' => $subject
            ]);

            
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully!'
            ]);

        } catch (\Exception $e) {
            // Log the detailed error
            Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'to' => $recipientEmail,
                'subject' => $subject
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display all bank accounts across all business entities for the current user.
     *
     * @return \Illuminate\View\View
     */
    public function bankAccountsIndex()
    {
        $user = Auth::user();
        $businessEntities = BusinessEntity::where('user_id', $user->id)->get();
        $bankAccounts = collect();
        
        foreach ($businessEntities as $entity) {
            $entityBankAccounts = $entity->bankAccounts()->with(['bankStatementEntries.transaction'])->get();
            $bankAccounts = $bankAccounts->merge($entityBankAccounts);
        }
        
        return view('bank-accounts.index', compact('bankAccounts', 'businessEntities'));
    }

    /**
     * Display all transactions across all business entities for the current user.
     *
     * @return \Illuminate\View\View
     */
    public function transactionsIndex()
    {
        $user = Auth::user();
        $businessEntities = BusinessEntity::where('user_id', $user->id)->get();
        $transactions = collect();
        
        foreach ($businessEntities as $entity) {
            $entityTransactions = $entity->transactions()->with(['bankStatementEntries'])->orderBy('date', 'desc')->get();
            $transactions = $transactions->merge($entityTransactions);
        }
        
        return view('transactions.index', compact('transactions', 'businessEntities'));
    }

} // End of BusinessEntityController class
