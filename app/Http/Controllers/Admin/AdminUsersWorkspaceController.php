<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TableSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUsersWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $users = self::paginatedUsers($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($users, $request),
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
        $tableSort = TableSort::resolve($request, ['name', 'email', 'status', 'last_login'], 'name', 'asc');

        $query = User::query()
            ->withCount([
                'businessEntities',
                'journalEntries',
                'realEstateCompanies',
                'notes',
                'reminders',
                'mailMessages',
                'mailLabels',
                'emails',
            ]);

        if (in_array($tableSort->column, ['name', 'email', 'last_login', 'status'], true)) {
            $tableSort->applyToQuery($query, [
                'name' => 'name',
                'email' => 'email',
                'last_login' => 'last_login_at',
                'status' => 'is_active',
            ], 'name');
        } else {
            $query->orderBy('name');
        }

        return $query
            ->paginate(20)
            ->withQueryString();
    }

    public static function tableSort(Request $request): TableSort
    {
        return TableSort::resolve($request, ['name', 'email', 'status', 'last_login'], 'name', 'asc');
    }

    public static function listHtml($users, ?Request $request = null): string
    {
        $request ??= request();

        return view('admin.users.partials.list', [
            'users' => $users,
            'tableSort' => self::tableSort($request),
        ])->render();
    }
}
