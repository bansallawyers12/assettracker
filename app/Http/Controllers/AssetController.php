<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
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
        $asset->load('notes');
        return view('assets.show', compact('businessEntity', 'asset'));
    }

    public function edit(BusinessEntity $businessEntity, Asset $asset)
    {
        return view('assets.edit', compact('businessEntity', 'asset'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
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
        $asset->delete();
        return redirect()->route('business-entities.show', $businessEntity->id)
            ->with('success', 'Asset deleted successfully');
    }

    public function createTenant(BusinessEntity $businessEntity, Asset $asset)
    {
        return view('assets.tenants.create', compact('businessEntity', 'asset'));
    }

    public function storeTenant(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'move_in_date' => 'nullable|date',
            'move_out_date' => 'nullable|date|after_or_equal:move_in_date',
            'notes' => 'nullable|string',
        ]);

        $asset->tenants()->create($validated);

        return redirect()->route('business-entities.assets.show', [$businessEntity->id, $asset->id])->with('success', 'Tenant added successfully!');
    }

    public function createLease(BusinessEntity $businessEntity, Asset $asset)
    {
        $tenants = $asset->tenants;
        return view('assets.leases.create', compact('businessEntity', 'asset', 'tenants'));
    }

    public function storeLease(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
        $validated = $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
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
        return view('assets.notes.create', compact('businessEntity', 'asset'));
    }

    public function storeNote(Request $request, BusinessEntity $businessEntity, Asset $asset)
    {
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
}