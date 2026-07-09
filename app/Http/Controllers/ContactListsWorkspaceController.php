<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactListResource;
use App\Models\BusinessEntity;
use App\Models\ContactList;
use Illuminate\Http\JsonResponse;

class ContactListsWorkspaceController extends Controller
{
    public function index(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $contacts = $businessEntity->contactLists()->latest()->get();

        return response()->json([
            'status' => true,
            'contacts' => ContactListResource::collection($contacts)->resolve(),
            'list_html' => view('business-entities.partials.contact-lists.list', [
                'businessEntity' => $businessEntity,
                'contactLists' => $contacts,
            ])->render(),
        ]);
    }

    public function createForm(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.contact-lists.form', [
                'businessEntity' => $businessEntity,
                'contactList' => null,
                'mode' => 'create',
            ])->render(),
        ]);
    }

    public function editForm(BusinessEntity $businessEntity, ContactList $contactList): JsonResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureBelongs($businessEntity, $contactList);

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.contact-lists.form', [
                'businessEntity' => $businessEntity,
                'contactList' => $contactList,
                'mode' => 'edit',
            ])->render(),
        ]);
    }

    private function ensureBelongs(BusinessEntity $businessEntity, ContactList $contactList): void
    {
        if ((int) $contactList->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }
}
