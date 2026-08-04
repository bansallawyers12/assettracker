<?php

namespace App\Http\Controllers;

use App\Models\ContactList;
use App\Models\BusinessEntity;
use App\Support\TableSort;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    /**
     * Display a listing of the contacts for a specific business entity.
     */
    public function index(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);

        $tableSort = TableSort::resolve($request, ['name', 'email', 'phone'], 'name', 'asc');

        $query = $businessEntity->contactLists();

        if ($tableSort->column === 'phone') {
            $query->orderByRaw('COALESCE(mobile_no, phone_no) '.$tableSort->order);
        } else {
            $tableSort->applyToQuery($query, [
                'name' => ['last_name', 'first_name'],
                'email' => 'email',
            ], 'name');
        }

        $contacts = $query->paginate(10)->withQueryString();

        return view('contact-lists.index', compact('businessEntity', 'contacts', 'tableSort'));
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

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Contact created successfully.',
                'contact' => (new \App\Http\Resources\ContactListResource($contact))->resolve(),
                'list_html' => view('business-entities.partials.contact-lists.list', [
                    'businessEntity' => $businessEntity,
                    'contactLists' => $businessEntity->contactLists()->latest()->get(),
                ])->render(),
            ]);
        }

        return redirect()->route('business-entities.show', $businessEntity->id)
            ->withFragment('tab_contact_lists')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact.
     */
    public function show(BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('view', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ((int) $contactList->business_entity_id !== (int) $businessEntity->id) {
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
        if ((int) $contactList->business_entity_id !== (int) $businessEntity->id) {
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
        if ((int) $contactList->business_entity_id !== (int) $businessEntity->id) {
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

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Contact updated successfully.',
                'contact' => (new \App\Http\Resources\ContactListResource($contactList))->resolve(),
                'list_html' => view('business-entities.partials.contact-lists.list', [
                    'businessEntity' => $businessEntity,
                    'contactLists' => $businessEntity->contactLists()->latest()->get(),
                ])->render(),
            ]);
        }

        return redirect()->route('business-entities.show', $businessEntity->id)
            ->withFragment('tab_contact_lists')
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(BusinessEntity $businessEntity, ContactList $contactList)
    {
        $this->authorize('update', $businessEntity);
        // Ensure the contact belongs to the business entity
        if ((int) $contactList->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
        
        $contactList->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Contact deleted successfully.',
                'list_html' => view('business-entities.partials.contact-lists.list', [
                    'businessEntity' => $businessEntity,
                    'contactLists' => $businessEntity->contactLists()->latest()->get(),
                ])->render(),
            ]);
        }

        return redirect()->route('business-entities.show', $businessEntity->id)
            ->withFragment('tab_contact_lists')
            ->with('success', 'Contact deleted successfully.');
    }
} 