@extends('layouts.app')

@section('title', 'User · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Users' => route('users.index'), 'Detail' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $user->name }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('update', $user)
                <a href="{{ route('users.edit', $user) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                    Edit
                </a>
            @endcan
            @can('deactivate', $user)
                @if(! $user->is_active)
                    <form method="POST" action="{{ route('users.activate', $user) }}" @submit.prevent="if(confirm('Activate this user?')) $event.target.submit()">
                        @csrf
                        <x-ui.button type="submit" variant="brand" size="sm">Activate</x-ui.button>
                    </form>
                @else
                    <form method="POST" action="{{ route('users.deactivate', $user) }}" @submit.prevent="if(confirm('Deactivate this user?')) $event.target.submit()">
                        @csrf
                        <x-ui.button type="submit" variant="danger" size="sm">Deactivate</x-ui.button>
                    </form>
                @endif
            @endcan
            @can('delete', $user)
                <form method="POST" action="{{ route('users.destroy', $user) }}" @submit.prevent="if(confirm('Delete this user?')) $event.target.submit()">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                </form>
            @endcan
        </div>
    </div>

    @can('resetPassword', $user)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Reset Password</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Set a new password for this user.</p>
            <form method="POST" action="{{ route('users.reset-password', $user) }}" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <x-ui.input name="password" type="password" placeholder="New password" class="flex-1" required />
                <x-ui.input name="password_confirmation" type="password" placeholder="Confirm password" class="flex-1" required />
                <x-ui.button type="submit" variant="secondary" size="sm">Reset Password</x-ui.button>
            </form>
        </div>
    @endcan

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            'Role' => ucwords(str_replace('_', ' ', $user->role)),
            'Status' => $user->is_active ? 'Active' : 'Disabled',
            'Created' => $user->created_at->format('Y-m-d H:i'),
            'Updated' => $user->updated_at->format('Y-m-d H:i'),
        ] as $label => $value)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $label }}</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $value }}</p>
            </div>
        @endforeach
    </div>
@endsection
