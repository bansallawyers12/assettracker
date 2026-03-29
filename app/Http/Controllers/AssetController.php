<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Note;
use App\Models\RealEstateCompany;
use App\Models\RealEstateCompanyContact;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        $assets = $businessEntity->assets()->with(['notes' => function ($query) {
            $query->where('is_reminder', true)->where('reminder_date', '<=', now());
        }])->paginate(15);

        return view('assets.index', compact('businessEntity', 'assets'));
    }

    /**
     * List all assets across business entities (matches dashboard scope).
     */
    public function indexAll()
    {
        $this->authorize('viewAny', Asset::class);

        $assets = Asset::query()
            ->whereHas('businessEntity')
            ->with('businessEntity')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return view('assets.index-all', compact('assets'));
    }

    public function create(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        return view('assets.create', compact('businessEntity'));
    }

    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

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
            'leases.tenant',
            'tenants.realEstateCompany.contacts',
        ]);

        $assetInvoices = collect();
        $invoiceSummary = ['ytd_invoiced' => 0.0, 'outstanding' => 0.0, 'ytd_paid' => 0.0];

        $documentCategories = $businessEntity->documentCategories()
            ->where('asset_id', $asset->id)
            ->with(['documents' => fn ($q) => $q->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (in_array($asset->asset_type, Asset::LEASABLE_ASSET_TYPES)) {
            $year = (int) now()->format('Y');
            $assetInvoices = Invoice::query()
                ->where('asset_id', $asset->id)
                ->with(['lease.tenant', 'lines'])
                ->orderByDesc('issue_date')
                ->get();

            $invoiceSummary = [
                'ytd_invoiced' => (float) Invoice::query()
                    ->where('asset_id', $asset->id)
                    ->whereYear('issue_date', $year)
                    ->sum('total_amount'),
                'outstanding' => (float) Invoice::query()
                    ->where('asset_id', $asset->id)
                    ->where('status', 'approved')
                    ->sum('total_amount'),
                'ytd_paid' => (float) Invoice::query()
                    ->where('asset_id', $asset->id)
                    ->where('status', 'paid')
                    ->whereYear('paid_at', $year)
                    ->sum('total_amount'),
            ];
        }

        return view('assets.show', compact('businessEntity', 'asset', 'assetInvoices', 'invoiceSummary', 'documentCategories'));
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

        if (! isset($fieldMap[$type])) {
            return redirect()->back()->with('error', 'Invalid due date type.');
        }

        $field = $fieldMap[$type];
        $asset->update([$field => null]);

        return redirect()->back()->with('success', ucfirst($type).' due date finalized!');
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

        if (! isset($fieldMap[$type])) {
            return redirect()->back()->with('error', 'Invalid due date type.');
        }

        $field = $fieldMap[$type];
        $currentDate = $asset->$field;
        if ($currentDate && $currentDate instanceof Carbon) {
            $asset->update([$field => $currentDate->addDays(3)]);

            return redirect()->back()->with('success', ucfirst($type).' due date extended by 3 days!');
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

        $realEstateCompanies = RealEstateCompany::query()
            ->orderBy('name')
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
            'real_estate_company_id' => [
                'nullable',
                Rule::exists('real_estate_companies', 'id'),
            ],
        ]);

        $isRealEstateManaged = $request->boolean('is_real_estate_managed');
        $isCreatingRealEstateCompany = $request->boolean('create_real_estate_company');
        $realEstateCompanyId = null;

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
                if (empty($validated['real_estate_company_id'])) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['real_estate_company_id' => 'Please select a real estate agency, or create a new one.']);
                }

                $realEstateCompanyId = (int) $validated['real_estate_company_id'];
            }
        }

        $leaseExpiryDate = null;
        // HTTP form values are strings; Carbon add*() requires int|float
        $leaseDurationValue = isset($validated['lease_duration_value']) && $validated['lease_duration_value'] !== ''
            ? (int) $validated['lease_duration_value']
            : null;
        $leaseDurationUnit = $validated['lease_duration_unit'] ?? null;
        $leaseStartDate = ! empty($validated['move_in_date']) ? Carbon::parse($validated['move_in_date']) : null;
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
            &$realEstateCompanyId,
            $leaseExpiryDate,
            $leaseDurationValue,
            $leaseDurationUnit,
            $reminderDays
        ) {
            if ($isRealEstateManaged && $isCreatingRealEstateCompany) {
                $contacts = collect($request->input('real_estate_contacts', []));
                $primaryContact = $contacts->first();
                $address = $request->input('address') ?: ($asset->address ?: null);
                $companyEmail = $primaryContact['email'] ?? $request->input('email');
                $companyPhone = $primaryContact['phone'] ?? $request->input('phone');

                $realEstateCompany = RealEstateCompany::create([
                    'user_id' => auth()->id(),
                    'name' => $request->input('real_estate_company_name'),
                    'email' => $companyEmail,
                    'phone' => $companyPhone,
                    'address' => $address,
                ]);

                $realEstateCompanyId = $realEstateCompany->id;

                foreach ($contacts as $contact) {
                    $fullName = trim((string) ($contact['contact_person_name'] ?? ''));
                    if ($fullName === '') {
                        continue;
                    }

                    RealEstateCompanyContact::create([
                        'real_estate_company_id' => $realEstateCompany->id,
                        'contact_person_name' => $fullName,
                        'email' => $contact['email'] ?? null,
                        'phone' => $contact['phone'] ?? null,
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
                'real_estate_company_id' => $realEstateCompanyId,
            ]);

            $this->createLeaseFromTenantIfMissing($asset, $tenant);

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

    /**
     * Create a lease row from tenant lease/rent fields (Leases tab) if none exists yet for this tenant.
     */
    public function syncLeasesFromTenants(BusinessEntity $businessEntity, Asset $asset)
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);

        $created = 0;
        foreach ($asset->tenants as $tenant) {
            if ($this->createLeaseFromTenantIfMissing($asset, $tenant)) {
                $created++;
            }
        }

        $message = $created > 0
            ? "{$created} lease(s) created from tenant records (Leases tab)."
            : 'No new leases needed: each tenant with a lease start date already has a lease, or no lease start date is set on your tenants.';

        return redirect()
            ->to(route('business-entities.assets.show', [$businessEntity->id, $asset->id]).'#tab_leases')
            ->with('success', $message);
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
     * @return RedirectResponse
     */
    public function finalizeNote(Note $note)
    {
        $note->update(['reminder_date' => null, 'is_reminder' => false]);

        return redirect()->back()->with('success', 'Reminder finalized.');
    }

    /**
     * Extend a note's reminder date by 3 days.
     *
     * @return RedirectResponse
     */
    public function extendNote(Note $note)
    {
        if ($note->reminder_date) {
            $note->update(['reminder_date' => Carbon::parse($note->reminder_date)->addDays(3)]);

            return redirect()->back()->with('success', 'Reminder extended by 3 days.');
        }

        return redirect()->back()->with('error', 'No valid reminder date to extend.');
    }

    /**
     * Nested routes resolve Asset by id only — ensure it belongs to the URL business entity.
     */
    private function ensureAssetBelongsToBusinessEntity(BusinessEntity $businessEntity, Asset $asset): void
    {
        $this->authorize('view', $businessEntity);

        if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }

    /**
     * Copy lease start, duration/expiry, rent, and notes from a tenant into the leases table (one row per tenant if missing).
     */
    private function createLeaseFromTenantIfMissing(Asset $asset, Tenant $tenant): bool
    {
        if (! $tenant->move_in_date) {
            return false;
        }

        if (Lease::query()->where('asset_id', $asset->id)->where('tenant_id', $tenant->id)->exists()) {
            return false;
        }

        $rentAmount = $tenant->rent_amount !== null && $tenant->rent_amount !== ''
            ? $tenant->rent_amount
            : 0;

        $paymentFrequency = match ($tenant->rent_frequency ?? '') {
            'Weekly' => 'Weekly',
            'Monthly' => 'Monthly',
            default => 'Monthly',
        };

        $leaseTermsParts = [];
        if ($tenant->lease_duration_value && $tenant->lease_duration_unit) {
            $leaseTermsParts[] = "Duration: {$tenant->lease_duration_value} {$tenant->lease_duration_unit}";
        }
        if ($tenant->lease_expiry_date) {
            $leaseTermsParts[] = 'Lease end: '.$tenant->lease_expiry_date->format('d/m/Y');
        }
        if ($tenant->lease_expiry_reminder_days !== null) {
            $leaseTermsParts[] = "Expiry reminder: {$tenant->lease_expiry_reminder_days} days before end";
        }
        if ($tenant->notes) {
            $leaseTermsParts[] = 'Notes: '.$tenant->notes;
        }
        if ($tenant->is_real_estate_managed) {
            $tenant->loadMissing('realEstateCompany');
            if ($tenant->realEstateCompany) {
                $leaseTermsParts[] = 'Managed by: '.$tenant->realEstateCompany->name;
            }
        }

        $leaseTerms = count($leaseTermsParts) ? implode('. ', $leaseTermsParts) : 'Synced from tenant record.';

        $endDate = $tenant->lease_expiry_date
            ? $tenant->lease_expiry_date->format('Y-m-d')
            : null;

        Lease::create([
            'asset_id' => $asset->id,
            'tenant_id' => $tenant->id,
            'rental_amount' => $rentAmount,
            'payment_frequency' => $paymentFrequency,
            'start_date' => $tenant->move_in_date->format('Y-m-d'),
            'end_date' => $endDate,
            'terms' => $leaseTerms,
        ]);

        return true;
    }
}
