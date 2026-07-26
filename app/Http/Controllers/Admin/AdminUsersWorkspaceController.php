<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUsersWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $users = self::paginatedUsers($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($users),
        ]);
    }

    public function createForm(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'html' => view('admin.users.partials.create-form')->render(),
        ]);
    }

    public function passwordForm(User $user): JsonResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return response()->json([
                'status' => false,
                'message' => __('The primary administrator password cannot be reset here. Update the database password via a secure console if needed; ADMIN_PASSWORD_HASH is bootstrap-only.'),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'html' => view('admin.users.partials.password-form', [
                'user' => $user,
            ])->render(),
        ]);
    }

    public static function paginatedUsers(Request $request)
    {
        return User::query()
            ->withCount([
                'businessEntities',
                'journalEntries',
                'realEstateCompanies',
                'notes',
                'reminders',
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    public static function listHtml($users): string
    {
        return view('admin.users.partials.list', [
            'users' => $users,
        ])->render();
    }
}
