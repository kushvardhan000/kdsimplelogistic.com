<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.index', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            if ($request->filled('email') || $request->filled('password') || $request->filled('current_password')) {
                ActivityLogger::failedLogin($request->email ?? $user->email, $request);

                return back()->withErrors([
                    'email' => 'Only Super Admins can modify email or password.',
                    'current_password' => 'Only Super Admins can modify email or password.',
                    'password' => 'Only Super Admins can modify email or password.',
                ]);
            }
        }

        $user->name = $request->name;

        $changes = [];

        if ($user->isSuperAdmin()) {
            $oldEmail = $user->email;
            $user->email = $request->email;
            $changes[] = ['field' => 'email', 'old' => $oldEmail, 'new' => $user->email];

            if ($request->filled('password')) {
                ActivityLogger::passwordChange($user, $request);
                $changes[] = ['field' => 'password', 'old' => '***', 'new' => '***'];
            }
        }

        $user->save();

        if (!empty($changes)) {
            ActivityLogger::profileUpdate($user, $changes, $request);
        }

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
