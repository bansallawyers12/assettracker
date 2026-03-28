<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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

    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
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

        return redirect()->route('admin.users.index')->with('status', __('User created successfully.'));
    }

    public function activate(User $user): RedirectResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return back()->with('error', __('The primary administrator account is always active.'));
        }

        $user->update(['is_active' => true]);

        return back()->with('status', __('User activated.'));
    }

    public function deactivate(User $user): RedirectResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return back()->with('error', __('You cannot deactivate the primary administrator.'));
        }

        if ($user->is(auth()->user())) {
            return back()->with('error', __('You cannot deactivate your own account.'));
        }

        $user->update([
            'is_active' => false,
            'remember_token' => null,
        ]);
        $this->flushDatabaseSessionsForUser($user);

        return back()->with('status', __('User deactivated. They can no longer sign in.'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $bag = 'password_user_'.$user->id;

        $validated = $request->validateWithBag($bag, [
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
            'remember_token' => null,
        ]);

        $this->flushDatabaseSessionsForUser($user);

        return back()->with('status', __('Password updated for :name.', ['name' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isPrimaryAdministrator()) {
            return back()->with('error', __('You cannot delete the primary administrator.'));
        }

        if ($user->is(auth()->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $this->flushDatabaseSessionsForUser($user);
        $user->delete();

        return back()->with('status', __('User deleted.'));
    }
}
