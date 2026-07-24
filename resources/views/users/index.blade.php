@extends('layouts.app')

@section('title', 'Users · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Users' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Users</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage team members and their account status.</p>
        </div>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-brand-600 px-3 text-xs font-medium text-white shadow-premium-sm hover:bg-brand-700">
                Invite User
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                    <th class="px-6 py-3.5">Name</th>
                    <th class="px-6 py-3.5">Email</th>
                    <th class="px-6 py-3.5">Role</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($users as $userItem)
                    <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $userItem->name }}</td>
                        <td class="px-6 py-4 text-xs">{{ $userItem->email }}</td>
                        <td class="px-6 py-4 text-xs uppercase">{{ $userItem->role }}</td>
                        <td class="px-6 py-4">
                            @if($userItem->is_active)
                                <x-ui.badge variant="success" dot>Active</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger" dot>Disabled</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('users.show', $userItem) }}" class="text-brand-600 hover:underline dark:text-brand-500">View</a>
                            @can('deactivate', $userItem)
                                @if(! $userItem->is_active)
                                    <form method="POST" action="{{ route('users.activate', $userItem) }}" class="inline" @submit.prevent="if(confirm('Activate this user?')) $event.target.submit()">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">Activate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('users.deactivate', $userItem) }}" class="inline" @submit.prevent="if(confirm('Deactivate this user?')) $event.target.submit()">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">Deactivate</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
