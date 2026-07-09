<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Remove persisted sessions for this user when using the database session driver.
     * Other drivers (file, redis, etc.) are not keyed by user_id here; remember_token is still cleared separately.
     */
    private function flushDatabaseSessionsForUser(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    public function index(Request $request): View
    {
        $users = AdminUsersWorkspaceController::paginatedUsers($request);

        return view('admin.users.index', compact('users'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $emailHash = hash_hmac('sha256', $email, config('app.key'));

        $request->merge(['email' => $email]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($emailHash): void {
                    if (User::where('email_hash', $emailHash)->exists()) {
                        $fail(__('The email has already been taken.'));
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (strcasecmp($email, strtolower(trim((string) config('admin.email')))) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => __('This address is reserved for the primary administrator.'),
                    'errors' => ['email' => [__('This address is reserved for the primary administrator.')]],
                ], 422);
            }

            return back()->withErrors(['email' => __('This address is reserved for the primary administrator.')])->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => $request->password,
            'email_verified_at' => now(),
            'password_changed_at' => now(),
            'is_active' => true,
        ]);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('User created successfully.'));
        }

        return redirect()->route('admin.users.index')->with('status', __('User created successfully.'));
    }

    public function activate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return $this->actionError($request, __('The primary administrator account is always active.'));
        }

        $user->update(['is_active' => true]);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('User activated.'));
        }

        return back()->with('status', __('User activated.'));
    }

    public function deactivate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return $this->actionError($request, __('You cannot deactivate the primary administrator.'));
        }

        if ($user->is(auth()->user())) {
            return $this->actionError($request, __('You cannot deactivate your own account.'));
        }

        $user->update([
            'is_active' => false,
            'remember_token' => null,
        ]);
        $this->flushDatabaseSessionsForUser($user);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('User deactivated. They can no longer sign in.'));
        }

        return back()->with('status', __('User deactivated. They can no longer sign in.'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
            'remember_token' => null,
        ]);

        $this->flushDatabaseSessionsForUser($user);

        $message = __('Password updated for :name.', ['name' => $user->name]);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return $this->actionError($request, __('You cannot delete the primary administrator.'));
        }

        if ($user->is(auth()->user())) {
            return $this->actionError($request, __('You cannot delete your own account.'));
        }

        $this->flushDatabaseSessionsForUser($user);
        $user->delete();

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('User deleted.'));
        }

        return back()->with('status', __('User deleted.'));
    }

    private function workspaceJsonResponse(Request $request, string $message): JsonResponse
    {
        $users = AdminUsersWorkspaceController::paginatedUsers($request);

        return response()->json([
            'status' => true,
            'message' => $message,
            'list_html' => AdminUsersWorkspaceController::listHtml($users),
        ]);
    }

    private function actionError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], 422);
        }

        return back()->with('error', $message);
    }
}
