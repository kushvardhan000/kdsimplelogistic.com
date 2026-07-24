<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%')
                ->orWhere('email', 'like', '%' . $request->string('search') . '%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $createdUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLogger::crud(
            action: 'create',
            module: 'users',
            recordId: $createdUser->id,
            summary: $createdUser->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only('name', 'email', 'role');
        $data['is_active'] = $request->boolean('is_active', $user->is_active);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $oldRole = $user->role;
        $oldStatus = $user->is_active;

        $user->update($data);

        $changes = [];
        if ($oldRole !== $user->role) {
            ActivityLogger::roleChange($user, $oldRole, $user->role, $request);
            $changes[] = ['field' => 'role', 'old' => $oldRole, 'new' => $user->role];
        }
        if ($oldStatus != $user->is_active) {
            ActivityLogger::statusChange('users', $user->id, $user->name, $oldStatus ? 'active' : 'inactive', $user->is_active ? 'active' : 'inactive', $request->user(), $request);
            $changes[] = ['field' => 'is_active', 'old' => $oldStatus, 'new' => $user->is_active];
        }

        if (!empty($changes)) {
            ActivityLogger::crud(
                action: 'update',
                module: 'users',
                recordId: $user->id,
                summary: $user->name,
                changes: $changes[0],
                user: $request->user(),
                request: $request
            );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $request = request();

        ActivityLogger::crud(
            action: 'delete',
            module: 'users',
            recordId: $user->id,
            summary: $user->name,
            user: $request->user(),
            request: $request
        );

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'current_password' => ['required', 'current_password'],
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        ActivityLogger::passwordChange($user, $request);

        return redirect()
            ->route('users.index')
            ->with('success', 'Password reset successfully.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $request = request();
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user->update(['is_active' => false]);

        ActivityLogger::statusChange('users', $user->id, $user->name, 'active', 'inactive', $request->user(), $request);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deactivated successfully.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('activate', $user);

        $request = request();
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user->update(['is_active' => true]);

        ActivityLogger::statusChange('users', $user->id, $user->name, 'inactive', 'active', $request->user(), $request);

        return redirect()
            ->route('users.index')
            ->with('success', 'User activated successfully.');
    }
}
