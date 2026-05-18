<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user()->loadMissing(['student', 'organization']);

        return view('account.profile', compact('user'));
    }

    public function editPassword(): View
    {
        return view('account.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($data['password']),
            'requires_password_change' => false,
        ]);

        return redirect()->route('account.password.edit')
            ->with('success', 'Password updated successfully.');
    }

    public function forceChange(Request $request): View|RedirectResponse
    {
        if (! $request->user()->requires_password_change) {
            return redirect($this->dashboardRoute($request->user()));
        }

        return view('auth.force-change-password');
    }

    public function updateForcedPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password_hash' => Hash::make($data['password']),
            'requires_password_change' => false,
        ]);

        return redirect($this->dashboardRoute($request->user()))
            ->with('success', 'Your password has been updated.');
    }

    private function dashboardRoute(User $user): string
    {
        if ($user->organization?->type === 'UNIVERSITY_WIDE') {
            return route('admin.dashboard');
        }

        return route('org.dashboard');
    }
}
