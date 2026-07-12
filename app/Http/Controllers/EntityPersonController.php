<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntityPersonResource;
use App\Models\EntityPerson;
use App\Models\BusinessEntity;
use App\Models\BankAccount;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EntityPersonController extends Controller
{
    /**
     * Display a listing of entity-person relationships.
     */
    public function index()
    {
        $entityPersons = EntityPerson::with(['businessEntity', 'person', 'trusteeEntity'])
            ->where('role_status', 'Active')
            ->get();
        return view('entity-persons.index', compact('entityPersons'));
    }

    /**
     * Show the form for creating a new entity-person relationship.
     */
    public function create($business_entity_id)
    {
        Log::info('Requested business_entity_id from route', ['id' => $business_entity_id]);

        $businessEntity = BusinessEntity::find($business_entity_id);

        if (!$businessEntity) {
            Log::warning('Business entity not found', ['id' => $business_entity_id]);
            return redirect()->route('business-entities.index')->withErrors(['error' => 'Business entity not found. Please select a valid entity.']);
        }

        if ($businessEntity->isTenancyContactOnly()) {
            return redirect()->route('business-entities.show', $businessEntity)
                ->withErrors(['error' => 'Company roles and officers apply to operating entities only, not tenancy or property manager contacts.']);
        }

        $persons = Person::query()
            ->get()
            ->sortBy(fn (Person $person) => mb_strtolower($person->displayName()), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $businessEntities = BusinessEntity::query()
            ->operationalEntities()
            ->where('entity_type', '!=', 'Trust')
            ->where('id', '!=', $businessEntity->id)
            ->orderBy('legal_name')
            ->get(); // Exclude trusts to prevent circular references

        return view('entity-persons.create', compact('businessEntity', 'persons', 'businessEntities'));
    }

    /**
     * Store a newly created entity-person relationship in storage.
     */
    public function store(Request $request)
    {
        // Log the incoming request data for debugging
        Log::info('Store Request Data', $request->all());

        // Validate the request - IMPORTANT: No unique validation here to allow multiple roles
        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'person_id' => 'nullable|exists:persons,id',
            'entity_trustee_id' => ['nullable', BusinessEntity::ruleExistsNonTrustCompany()],
            'role' => 'required|in:Director,Secretary,Shareholder,Trustee,Beneficiary,Settlor,Owner',
            'appointment_date' => 'required|date',
            'resignation_date' => 'nullable|date|after:appointment_date',
            'role_status' => 'required|in:Active,Resigned',
            'shares_percentage' => 'nullable|numeric|between:0,100',
            'authority_level' => 'nullable|in:Full,Limited',
            'asic_due_date' => 'nullable|date|after:today',
            // New person fields - only validate these if creating a new person
            'first_name' => 'required_if:create_new_person,1|string|max:255|nullable',
            'last_name' => 'required_if:create_new_person,1|string|max:255|nullable',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:15',
            'tfn' => 'nullable|string|max:9',
            'abn' => 'nullable|string|max:11',
        ], [
            'business_entity_id.required' => 'The business entity is required.',
            'role.required' => 'The role is required.',
            'appointment_date.required' => 'The appointment date is required.',
            'role_status.required' => 'The role status is required.',
            'first_name.required_if' => 'The first name is required when creating a new person.',
            'last_name.required_if' => 'The last name is required when creating a new person.',
        ]);

        // Handle new person creation if checkbox is checked
        $personId = $request->filled('person_id') ? $request->person_id : null;
        $entityTrusteeId = $request->filled('entity_trustee_id') ? $request->entity_trustee_id : null;

        if ($entityTrusteeId) {
            $personId = null;
        } elseif ($request->has('create_new_person') && $request->create_new_person == 1) {
            // Email is stored encrypted so a raw WHERE clause cannot match; compare
            // after Eloquent decrypts each row via getAttribute().
            if ($request->email && Person::all()->contains(fn ($p) => $p->email === $request->email)) {
                return $this->personFormError($request, ['email' => 'A person with this email already exists. Please use the existing person instead.']);
            }
            
            $personData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'tfn' => $request->tfn,
                'abn' => $request->abn,
            ];
            try {
                $person = Person::create($personData);
                Log::info('Created new person', $person->toArray());
                $personId = $person->id;
            } catch (\Exception $e) {
                Log::error('Failed to create new person', ['error' => $e->getMessage(), 'data' => $personData]);
                return $this->personFormError($request, ['error' => 'Failed to create new person: ' . $e->getMessage()]);
            }
        }

        // Ensure either person_id or entity_trustee_id is filled, but not both
        if (($personId && $entityTrusteeId) || (! $personId && ! $entityTrusteeId)) {
            Log::warning('Validation failed: Either person_id or entity_trustee_id must be filled, but not both.', ['person_id' => $personId, 'entity_trustee_id' => $entityTrusteeId]);
            return $this->personFormError($request, ['error' => 'Either an existing person or a trustee company must be selected, but not both.']);
        }

        // Prepare data for EntityPerson creation
        $entityPersonData = [
            'business_entity_id' => $request->business_entity_id,
            'person_id' => $personId,
            'entity_trustee_id' => $entityTrusteeId,
            'appointor_entity_id' => null,
            'role' => $request->role,
            'appointment_date' => $request->appointment_date,
            'resignation_date' => $request->resignation_date,
            'role_status' => $request->role_status,
            'shares_percentage' => $request->shares_percentage,
            'authority_level' => $request->authority_level,
            'asic_due_date' => $request->asic_due_date,
        ];

        // Log the data to be inserted
        Log::info('EntityPerson Data to Insert', $entityPersonData);

        try {
            // Create the relationship without enforcing any uniqueness
            $entityPerson = EntityPerson::create($entityPersonData);
            Log::info('Created EntityPerson', $entityPerson->toArray());
        } catch (\Exception $e) {
            Log::error('Failed to create EntityPerson', [
                'error' => $e->getMessage(), 
                'data' => $entityPersonData, 
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return to the form with a more descriptive error message
            return $this->personFormError($request, [
                'error' => 'Failed to create relationship: ' . $e->getMessage() .
                ' This may be due to a database constraint. We have attempted to remove unique constraints on entity_person table.',
            ]);
        }

        // Redirect back to the business entity page
        if ($request->expectsJson()) {
            $entityPerson->load(['person', 'trusteeEntity']);

            return response()->json([
                'status' => true,
                'message' => 'Entity-Person relationship created successfully.',
                'entity_person' => (new EntityPersonResource($entityPerson))->resolve(),
            ]);
        }

        return redirect()->route('business-entities.show', $request->business_entity_id)
            ->withFragment('tab_persons')
            ->with('success', 'Entity-Person relationship created successfully.');
    }

    /**
     * Display the specified entity-person relationship.
     */
    public function show(EntityPerson $entityPerson)
    {
        $businessEntity = $entityPerson->businessEntity; // Load the related business entity
        return view('entity-persons.show', compact('entityPerson', 'businessEntity'));
    }

    /**
     * Show the form for editing the specified entity-person relationship.
     */
    public function edit(EntityPerson $entityPerson)
    {
        $entityPerson->load(['businessEntity', 'person', 'trusteeEntity', 'appointorEntity']);
        $businessEntity = $entityPerson->businessEntity;
        $businessEntities = BusinessEntity::query()
            ->where('entity_type', '!=', 'Trust')
            ->where('id', '!=', $businessEntity->id)
            ->where(function ($query) use ($entityPerson) {
                $query->operationalEntities();

                if ($entityPerson->entity_trustee_id) {
                    $query->orWhere('id', $entityPerson->entity_trustee_id);
                }
            })
            ->orderBy('legal_name')
            ->get();
        $persons = Person::query()
            ->get()
            ->sortBy(fn (Person $person) => mb_strtolower($person->displayName()), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('entity-persons.edit', compact('entityPerson', 'businessEntity', 'businessEntities', 'persons'));
    }

    /**
     * Update the specified entity-person relationship in storage.
     */
    public function update(Request $request, EntityPerson $entityPerson)
    {
        $validated = $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'person_id' => 'nullable|exists:persons,id',
            'entity_trustee_id' => ['nullable', BusinessEntity::ruleExistsNonTrustCompany()],
            'role' => 'required|in:Director,Secretary,Shareholder,Trustee,Beneficiary,Settlor,Appointor,Owner',
            'appointment_date' => 'required|date',
            'resignation_date' => 'nullable|date|after:appointment_date',
            'role_status' => 'required|in:Active,Resigned',
            'shares_percentage' => 'nullable|numeric|between:0,100',
            'authority_level' => 'nullable|in:Full,Limited',
            'asic_due_date' => 'nullable|date',
        ]);

        $personId = $request->filled('person_id') ? $request->person_id : null;
        $entityTrusteeId = $request->filled('entity_trustee_id') ? $request->entity_trustee_id : null;

        // Ensure either person_id or entity_trustee_id is filled, but not both
        // Exception: legacy Appointor rows preserve hidden link fields
        if ($request->role !== 'Appointor') {
            if (($personId && $entityTrusteeId) || (! $personId && ! $entityTrusteeId)) {
                return $this->personFormError($request, ['error' => 'Either an existing person or a trustee company must be selected, but not both.']);
            }
        }

        $data = [
            'business_entity_id' => $validated['business_entity_id'],
            'role' => $validated['role'],
            'appointment_date' => $validated['appointment_date'],
            'resignation_date' => $validated['resignation_date'] ?? null,
            'role_status' => $validated['role_status'],
            'shares_percentage' => $validated['shares_percentage'] ?? null,
            'authority_level' => $validated['authority_level'] ?? null,
            'asic_due_date' => $validated['asic_due_date'] ?? null,
        ];

        if ($request->role === 'Appointor') {
            // Legacy appointor rows — preserve existing link fields from the form
            $data['person_id'] = $personId;
            $data['appointor_entity_id'] = $request->filled('appointor_entity_id') ? $request->appointor_entity_id : null;
            $data['entity_trustee_id'] = null;
        } else {
            $data['person_id'] = $personId;
            $data['entity_trustee_id'] = $entityTrusteeId;
            $data['appointor_entity_id'] = null;
        }

        $entityPerson->update($data);
        $entityPerson->load(['person', 'trusteeEntity']);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Entity-Person relationship updated successfully.',
                'entity_person' => (new EntityPersonResource($entityPerson))->resolve(),
            ]);
        }

        return redirect()->route('business-entities.show', $entityPerson->business_entity_id)
            ->withFragment('tab_persons')
            ->with('success', 'Entity-Person relationship updated successfully.');
    }

    /**
     * Remove the specified entity-person relationship from storage.
     */
    public function destroy(EntityPerson $entityPerson)
    {
        $entityPerson->delete();

        return redirect()->route('entity-persons.index')->with('success', 'Entity-Person relationship deleted successfully.');
    }

    /**
     * Finalize the ASIC due date for an entity-person relationship.
     */
    public function finalizeDueDate(EntityPerson $entityPerson)
    {
        $entityPerson->update([
            'asic_updated' => true,
            'asic_due_date' => null,
        ]);

        return redirect()->route('dashboard')->with('success', 'ASIC due date finalized successfully.');
    }

    /**
     * Extend the ASIC due date for an entity-person relationship by 30 days.
     */
    public function extendDueDate(EntityPerson $entityPerson)
    {
        if ($entityPerson->asic_due_date) {
            $newDueDate = \Carbon\Carbon::parse($entityPerson->asic_due_date)->addDays(30);
            $entityPerson->update([
                'asic_due_date' => $newDueDate,
            ]);
            return redirect()->route('dashboard')->with('success', 'ASIC due date extended by 30 days.');
        }

        return redirect()->route('dashboard')->with('error', 'No ASIC due date to extend.');
    }

    /**
     * Display all persons.
     */
    public function indexPersons()
    {
        $persons = Person::with(['entityPersons.businessEntity'])
            ->has('entityPersons')
            ->paginate(15);
        
        return view('persons.index', compact('persons'));
    }

    /**
     * Show the form for creating a new person.
     */
    public function createPerson()
    {
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        return view('persons.create', compact('businessEntities'));
    }

    /**
     * Store a newly created person.
     */
    public function storePerson(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
            'role' => 'required|string|max:255',
            'appointment_date' => 'required|date',
            'role_status' => 'required|in:Active,Inactive',
            'asic_due_date' => 'nullable|date',
        ]);

        // Create the person
        $person = Person::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'date_of_birth' => $validated['date_of_birth'],
        ]);

        // Create the entity-person relationship
        EntityPerson::create([
            'business_entity_id' => $validated['business_entity_id'],
            'person_id' => $person->id,
            'role' => $validated['role'],
            'appointment_date' => $validated['appointment_date'],
            'role_status' => $validated['role_status'],
            'asic_due_date' => $validated['asic_due_date'],
        ]);

        return redirect()->route('persons.index')->with('success', 'Person created successfully.');
    }

    /**
     * Display all roles for a specific person across all entities.
     */
    public function showPerson(Person $person)
    {
        // Get all entity-person relationships for this person
        $entityPersons = EntityPerson::where('person_id', $person->id)
            ->with(['businessEntity', 'person', 'trusteeEntity'])
            ->orderBy('business_entity_id')
            ->orderBy('role')
            ->get();

        // Group by business entity for better organization
        $groupedRoles = $entityPersons->groupBy('business_entity_id');

        $heldBankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->withDeleteCounts()
            ->where('holder_type', BankAccount::HOLDER_PERSON)
            ->where('holder_person_id', $person->id)
            ->with(['businessEntity', 'holderEntity', 'holderPerson'])
            ->orderBy('account_name')
            ->get();

        $heldBankAccountGroups = BankAccount::groupedByHolder($heldBankAccounts);

        return view('persons.show', compact('person', 'entityPersons', 'groupedRoles', 'heldBankAccounts', 'heldBankAccountGroups'));
    }

    private function personFormError(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => collect($errors)->flatten()->first(),
                'errors' => collect($errors)->map(fn ($message) => is_array($message) ? $message : [$message])->all(),
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors($errors);
    }
}
