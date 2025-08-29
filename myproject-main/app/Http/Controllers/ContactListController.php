<?php

namespace App\Http\Controllers;

use App\Models\ContactList;
use App\Models\BusinessEntity;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    /**
     * Display a listing of the contacts for a specific business entity.
     */
    public function index(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        $contacts = $businessEntity->contactLists()->latest()->paginate(10);
        return view('contact-lists.index', compact('businessEntity', 'contacts'));
    }

    /**
     * Show the form for creating a new contact for a specific business entity.
     */
    public function create(BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        return view('contact-lists.create', compact('businessEntity'));
    }

    /**
     * Store a newly created contact for a specific business entity in storage.
     */
    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone_no' => 'nullable|string|max:20',
            'mobile_no' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'zip_code' => 'nullable|string|max:20',
        ]);

        $contact = $businessEntity->contactLists()->create($validated);

        return redirect()->route('business-entities.contact-lists.index', $businessEntity)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact.
     */
    public function show(BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('view', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ($contactList->business_entity_id !== $businessEntity->id) {
            abort(404);
        }
        return view('contact-lists.show', compact('businessEntity', 'contactList'));
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('update', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ($contactList->business_entity_id !== $businessEntity->id) {
            abort(404);
        }
        return view('contact-lists.edit', compact('businessEntity', 'contactList'));
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('update', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ($contactList->business_entity_id !== $businessEntity->id) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'email' => 'nullable|email|max:255',
            'phone_no' => 'nullable|string|max:20',
            'mobile_no' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'zip_code' => 'nullable|string|max:20',
        ]);

        $contactList->update($validated);

        return redirect()->route('business-entities.contact-lists.index', $businessEntity)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('update', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ($contactList->business_entity_id !== $businessEntity->id) {
            abort(404);
        }
        
        $contactList->delete();

        return redirect()->route('business-entities.contact-lists.index', $businessEntity)
            ->with('success', 'Contact deleted successfully.');
    }
} 