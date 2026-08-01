<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->isPrimaryAdministrator()) {
            return Redirect::route('profile.edit')
                ->withErrors([
                    'userDeletion' => __('The primary administrator account cannot be deleted.'),
                ], 'userDeletion');
        }

        if (! $user->canBeDeleted()) {
            return Redirect::route('profile.edit')
                ->withErrors([
                    'userDeletion' => $user->deleteBlockedReason()
                        ?? __('This account cannot be deleted.'),
                ], 'userDeletion');
        }

        Auth::logout();

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            Auth::login($user);

            return Redirect::route('profile.edit')
                ->withErrors([
                    'userDeletion' => __('This account cannot be deleted because related records still exist. Ask an administrator to reassign data or deactivate the account instead.'),
                ], 'userDeletion');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
