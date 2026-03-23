<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserManagementController extends Controller
{
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
        ]);

        return redirect()->route('admin.users.create')->with('status', __('User created successfully.'));
    }
}
