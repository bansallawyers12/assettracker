<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use Illuminate\Http\JsonResponse;

class EntityShowWorkspaceController extends Controller
{
    public function notesIndex(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $notes = $businessEntity->notes()->where('is_reminder', false)->orderByDesc('created_at')->get();

        return response()->json([
            'status' => true,
            'list_html' => view('business-entities.partials.notes.list', [
                'businessEntity' => $businessEntity,
                'notes' => $notes,
            ])->render(),
        ]);
    }

    public function profileForm(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.profile.form', [
                'businessEntity' => $businessEntity,
            ])->render(),
        ]);
    }
}
