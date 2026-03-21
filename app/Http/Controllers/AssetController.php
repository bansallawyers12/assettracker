<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(BusinessEntity $businessEntity)
    {
        $assets = $businessEntity->assets()->with(['notes' => function($query) {
            $query->where('is_reminder', true)->where('reminder_date', '<=', now());
        }])->paginate(15);
        
        return view('assets.index', compact('businessEntity', 'assets'));
    }

    public function create(BusinessEntity $businessEntity)
    {
        return view('assets.create', compact('businessEntity'));
    }

    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $validatedData = $request->validate([
            'asset_type' => 'nullable|in:Car,House Owned,House Rented,Warehouse,Land,Office,Shop,Real Estate',
            'name' => 'required|string|max:255',
            'acquisition_cost' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'acquisition_date' => 'required|date',
            'status' => 'nullable|in:Active,Inactive,Sold,Under Maintenance',
            'description' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'registration_due_date' => 'nullable|date',
            'insurance_company' => 'nullable|string',
            'insurance_due_date' => 'nullable|date',
            'insurance_amount' => 'nullable|numeric|min:0',
            'vin_number' => 'nullable|string',
            'mileage' => 'nullable|integer',
            'fuel_type' => 'nullable|in:Petrol,Diesel,Electric,Hybrid',
            'service_due_date' => 'nullable|date',
            'vic_roads_updated' => 'nullable|boolean',
            'address' => 'nullable|string',
            'square_footage' => 'nullable|integer',
            'council_rates_amount' => 'nullable|numeric|min:0',
            'council_rates_due_date' => 'nullable|date',
            'owners_corp_amount' => 'nullable|numeric|min:0',
            'owners_corp_due_date' => 'nullable|date',
            'land_tax_amount' => 'nullable|numeric|min:0',
            'land_tax_due_date' => 'nullable|date',
            'sro_updated' => 'nullable|boolean',
            'real_estate_percentage' => 'nullable|numeric|min:0|max:100',
            'rental_income' => 'nullable|numeric|min:0',
        ]);

        $validatedData['asset_type'] = $validatedData['asset_type'] ?? 'Car';
        $validatedData['current_value'] = $validatedData['current_value'] ?? 0;
        $validatedData['status'] = $validatedData['status'] ?? 'Active';

        $assetData = array_merge($validatedData, [
            'business_entity_id' => $businessEntity->id,
            'user_id' => auth()->id(),
        ]);

        $asset = $businessEntity->assets()->create($assetData);

        return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
            ->with('success', 'Asset created successfully');
    }

    public function show(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $asset->load([
            'notes',
            'tenants.realEstateCompany.persons.person',
        ]);

        return view('assets.show', compact('businessEntity', 'asset'));
    }

    public function edit(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        return view('assets.edit', compact('businessEntity', 'asset'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $validatedData = $request->validate([
            'asset_type' => 'required|in:Car,House Owned,House Rented,Warehouse,Land,Office,Shop,Real Estate',
            'name' => 'required|string|max:255',
            'acquisition_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'acquisition_date' => 'nullable|date',
            'description' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'registration_due_date' => 'nullable|date',
            'insurance_company' => 'nullable|string',
            'insurance_due_date' => 'nullable|date',
            'insurance_amount' => 'nullable|numeric|min:0',
            'vin_number' => 'nullable|string',
            'mileage' => 'nullable|integer',
            'fuel_type' => 'nullable|in:Petrol,Diesel,Electric,Hybrid',
            'service_due_date' => 'nullable|date',
            'vic_roads_updated' => 'nullable|boolean',
            'address' => 'nullable|string',
            'square_footage' => 'nullable|integer',
            'council_rates_amount' => 'nullable|numeric|min:0',
            'council_rates_due_date' => 'nullable|date',
            'owners_corp_amount' => 'nullable|numeric|min:0',
            'owners_corp_due_date' => 'nullable|date',
            'land_tax_amount' => 'nullable|numeric|min:0',
            'land_tax_due_date' => 'nullable|date',
            'sro_updated' => 'nullable|boolean',
            'real_estate_percentage' => 'nullable|numeric|min:0|max:100',
            'rental_income' => 'nullable|numeric|min:0',
        ]);

        $asset->update($validatedData);

        return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])
            ->with('success', 'Asset updated successfully');
    }

    public function finalizeDueDate(Request $request, BusinessEntity $businessEntity, Asset $asset, $type)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $fieldMap = [
            'registration' => 'registration_due_date',
            'insurance' => 'insurance_due_date',
            'service' => 'service_due_date',
            'council_rates' => 'council_rates_due_date',
            'owners_corp' => 'owners_corp_due_date',
            'land_tax' => 'land_tax_due_date',
        ];

        if (!isset($fieldMap[$type])) {
            return redirect()->back()->with('error', 'Invalid due date type.');
        }

        $field = $fieldMap[$type];
        $asset->update([$field => null]);

        return redirect()->back()->with('success', ucfirst($type) . ' due date finalized!');
    }

    public function extendDueDate(Request $request, BusinessEntity $businessEntity, Asset $asset, $type)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $fieldMap = [
            'registration' => 'registration_due_date',
            'insurance' => 'insurance_due_date',
            'service' => 'service_due_date',
            'council_rates' => 'council_rates_due_date',
            'owners_corp' => 'owners_corp_due_date',
            'land_tax' => 'land_tax_due_date',
        ];

        if (!isset($fieldMap[$type])) {
            return redirect()->back()->with('error', 'Invalid due date type.');
        }

        $field = $fieldMap[$type];
        $currentDate = $asset->$field;
        if ($currentDate && $currentDate instanceof \Carbon\Carbon) {
            $asset->update([$field => $currentDate->addDays(3)]);
            return redirect()->back()->with('success', ucfirst($type) . ' due date extended by 3 days!');
        }

        Log::warning("No due date found for {$type} on asset {$asset->id}");
        return redirect()->back()->with('error', 'No valid due date to extend.');
    }

    public function destroy(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $asset->delete();
        return redirect()->route('business-entities.show', $businessEntity->id)
            ->with('success', 'Asset deleted successfully');
    }

    public function createTenant(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $realEstateCompanies = BusinessEntity::query()
            ->where('user_id', auth()->id())
            ->where('entity_type', 'Company')
            ->orderBy('legal_name')
            ->get();

        return view('assets.tenants.create', compact('businessEntity', 'asset', 'realEstateCompanies'));
    }

    public function storeTenant(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'move_in_date' => 'nullable|date',
            'lease_duration_value' => 'nullable|integer|min:1|required_with:lease_duration_unit',
            'lease_duration_unit' => 'nullable|in:days,weeks,months,years|required_with:lease_duration_value',
            'lease_expiry_reminder_days' => 'nullable|integer|min:0|max:3650',
            'rent_amount' => 'nullable|numeric|min:0',
            'rent_frequency' => 'nullable|in:Weekly,Monthly|required_with:rent_amount',
            'notes' => 'nullable|string',
            'is_real_estate_managed' => 'nullable|boolean',
            'create_real_estate_company' => 'nullable|boolean',
            'real_estate_business_entity_id' => [
                'nullable',
                Rule::exists('business_entities', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->where('entity_type', 'Company');
                }),
            ],
        ]);

        $isRealEstateManaged = $request->boolean('is_real_estate_managed');
        $isCreatingRealEstateCompany = $request->boolean('create_real_estate_company');
        $realEstateBusinessEntityId = null;

        if ($isRealEstateManaged) {
            if ($isCreatingRealEstateCompany) {
                $request->validate([
                    'real_estate_company_name' => 'required|string|max:255',
                    'real_estate_contacts' => 'required|array|min:1',
                    'real_estate_contacts.*.contact_person_name' => 'required|string|max:255',
                    'real_estate_contacts.*.email' => 'required|email|max:255',
                    'real_estate_contacts.*.phone' => 'required|string|max:20',
                ]);
            } else {
                if (empty($validated['real_estate_business_entity_id'])) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['real_estate_business_entity_id' => 'Please select a real estate company, or create a new one.']);
                }

                $realEstateBusinessEntityId = (int) $validated['real_estate_business_entity_id'];
            }
        }

        $leaseExpiryDate = null;
        // HTTP form values are strings; Carbon add*() requires int|float
        $leaseDurationValue = isset($validated['lease_duration_value']) && $validated['lease_duration_value'] !== ''
            ? (int) $validated['lease_duration_value']
            : null;
        $leaseDurationUnit = $validated['lease_duration_unit'] ?? null;
        $leaseStartDate = !empty($validated['move_in_date']) ? Carbon::parse($validated['move_in_date']) : null;
        $reminderDays = array_key_exists('lease_expiry_reminder_days', $validated)
            && $validated['lease_expiry_reminder_days'] !== ''
            ? (int) $validated['lease_expiry_reminder_days']
            : null;

        if ($leaseStartDate && $leaseDurationValue && $leaseDurationUnit) {
            $leaseExpiryDate = match ($leaseDurationUnit) {
                'days' => $leaseStartDate->copy()->addDays($leaseDurationValue),
                'weeks' => $leaseStartDate->copy()->addWeeks($leaseDurationValue),
                'months' => $leaseStartDate->copy()->addMonths($leaseDurationValue),
                'years' => $leaseStartDate->copy()->addYears($leaseDurationValue),
                default => null,
            };
        }

        DB::transaction(function () use (
            $request,
            $asset,
            $businessEntity,
            $validated,
            $isRealEstateManaged,
            $isCreatingRealEstateCompany,
            &$realEstateBusinessEntityId,
            $leaseExpiryDate,
            $leaseDurationValue,
            $leaseDurationUnit,
            $reminderDays
        ) {
            if ($isRealEstateManaged && $isCreatingRealEstateCompany) {
                $contacts = collect($request->input('real_estate_contacts', []));
                $primaryContact = $contacts->first();
                $registeredAddress = $request->input('address') ?: ($asset->address ?: 'Address not provided');
                $registeredEmail = $primaryContact['email'] ?? $request->input('email') ?? 'no-reply@assettracker.local';
                $rawPhone = $primaryContact['phone'] ?? $request->input('phone') ?? '0000000000';
                // Schema may be VARCHAR(15) on some DBs — avoid truncation errors
                $registeredPhone = mb_substr((string) $rawPhone, 0, 15);

                $realEstateCompany = BusinessEntity::create([
                    'legal_name' => $request->input('real_estate_company_name'),
                    'trading_name' => $request->input('real_estate_company_name'),
                    'entity_type' => 'Company',
                    'registered_address' => $registeredAddress,
                    'registered_email' => $registeredEmail,
                    'phone_number' => $registeredPhone,
                    'user_id' => auth()->id(),
                    'status' => 'Active',
                ]);

                $realEstateBusinessEntityId = $realEstateCompany->id;

                foreach ($contacts as $contact) {
                    $fullName = trim((string) ($contact['contact_person_name'] ?? ''));
                    if ($fullName === '') {
                        continue;
                    }

                    [$firstName, $lastName] = $this->splitContactName($fullName);

                    $person = Person::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $contact['email'] ?? null,
                        'phone_number' => $contact['phone'] ?? null,
                        'status' => 'Active',
                    ]);

                    EntityPerson::create([
                        'business_entity_id' => $realEstateCompany->id,
                        'person_id' => $person->id,
                        'role' => 'Owner',
                        'appointment_date' => now()->toDateString(),
                        'role_status' => 'Active',
                    ]);
                }
            }

            $tenant = $asset->tenants()->create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'move_in_date' => $validated['move_in_date'] ?? null,
                'lease_duration_value' => $leaseDurationValue,
                'lease_duration_unit' => $leaseDurationUnit,
                'lease_expiry_date' => $leaseExpiryDate,
                'lease_expiry_reminder_days' => $reminderDays,
                'rent_amount' => $validated['rent_amount'] ?? null,
                'rent_frequency' => $validated['rent_frequency'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_real_estate_managed' => $isRealEstateManaged,
                'real_estate_business_entity_id' => $realEstateBusinessEntityId,
            ]);

            if ($leaseExpiryDate && $reminderDays !== null) {
                $reminderDate = $leaseExpiryDate->copy()->subDays($reminderDays);
                $reminderDate = $reminderDate->lt(now()) ? now() : $reminderDate;

                $asset->notes()->create([
                    'content' => "Lease expiry reminder for tenant {$tenant->name}. Lease ends on {$leaseExpiryDate->format('d/m/Y')}.",
                    'user_id' => auth()->id(),
                    'is_reminder' => true,
                    'reminder_date' => $reminderDate,
                    'business_entity_id' => $businessEntity->id,
                    'asset_id' => $asset->id,
                ]);
            }
        });

        return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])->with('success', 'Tenant added successfully!');
    }

    public function createLease(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $tenants = $asset->tenants;
        return view('assets.leases.create', compact('businessEntity', 'asset', 'tenants'));
    }

    public function storeLease(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $validated = $request->validate([
            'tenant_id' => [
                'nullable',
                Rule::exists('tenants', 'id')->where('asset_id', $asset->id),
            ],
            'rental_amount' => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:Weekly,Fortnightly,Monthly,Quarterly,Yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'terms' => 'nullable|string',
        ]);

        $asset->leases()->create($validated);

        return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])->with('success', 'Lease added successfully!');
    }

    public function createNote(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        return view('assets.notes.create', compact('businessEntity', 'asset'));
    }

    public function storeNote(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $request->validate([
            'content' => 'required|string',
            'is_reminder' => 'boolean',
            'reminder_date' => 'nullable|date|after_or_equal:today',
            'repeat_type' => 'nullable|in:none,monthly,quarterly,annual',
            'repeat_end_date' => 'nullable|date|after_or_equal:reminder_date',
        ]);

        $asset->notes()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
            'is_reminder' => $request->is_reminder ?? false,
            'reminder_date' => $request->reminder_date,
            'repeat_type' => $request->repeat_type,
            'repeat_end_date' => $request->repeat_end_date,
            'business_entity_id' => $businessEntity->id,
            'asset_id' => $asset->id,
        ]);

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    public function destroyNote(BusinessEntity $businessEntity, Asset $asset, Note $note)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        if ((int) $note->asset_id !== (int) $asset->id) {
            abort(404);
        }

        $note->delete();
        return redirect()->back()->with('success', 'Note deleted successfully.');
    }

    /**
     * Finalize a note by removing its reminder status.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\RedirectResponse
     */
    public function finalizeNote(Note $note)
    {
        $note->update(['reminder_date' => null, 'is_reminder' => false]);
        return redirect()->back()->with('success', 'Reminder finalized.');
    }

    /**
     * Extend a note's reminder date by 3 days.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\RedirectResponse
     */
    public function extendNote(Note $note)
    {
        if ($note->reminder_date) {
            $note->update(['reminder_date' => Carbon::parse($note->reminder_date)->addDays(3)]);
            return redirect()->back()->with('success', 'Reminder extended by 3 days.');
        }
        return redirect()->back()->with('error', 'No valid reminder date to extend.');
    }

    private function splitContactName(string $fullName): array
    {
        $nameParts = preg_split('/\s+/', trim($fullName)) ?: [];
        $firstName = $nameParts[0] ?? 'Contact';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Contact';

        return [$firstName, $lastName];
    }

    /**
     * Nested routes resolve Asset by id only — ensure it belongs to the URL business entity.
     */
    private function ensureAssetBelongsToBusinessEntity(BusinessEntity $businessEntity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }
}