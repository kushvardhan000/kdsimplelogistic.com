@props([
    'variant' => 'sidebar',
])

<aside
    x-cloak
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-zinc-200 bg-white transition-all duration-300 ease-out-expo dark:border-zinc-800 dark:bg-zinc-900"
    :class="(sidebarOpen || isDesktop) ? 'translate-x-0' : '-translate-x-full' + ' ' + (sidebarIconOnly ? 'w-16' : 'w-64')"
>
    <div class="flex h-16 items-center border-b border-zinc-200 px-4 dark:border-zinc-800">
        <div class="flex items-center gap-2.5 overflow-hidden">
            <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">T</div>
            <span x-show="!sidebarIconOnly" x-cloak class="font-semibold text-zinc-950 dark:text-zinc-50 transition-opacity duration-200 whitespace-nowrap">Transport</span>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto p-2 md:p-3">
        <div>
            <span x-show="!sidebarIconOnly" x-cloak class="px-3 text-xxs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 transition-opacity duration-200">Core</span>
            <div class="mt-2 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors relative"
                   :class="isActive('{{ route('dashboard') }}')
                        ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
                        : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-50'"
                   :title="sidebarIconOnly ? 'Dashboard' : ''">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"/></svg>
                    <span x-show="!sidebarIconOnly" x-cloak class="transition-opacity duration-200 whitespace-nowrap">Dashboard</span>
                </a>
                <a href="{{ route('transport-logs.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors relative"
                   :class="isActive('{{ route('transport-logs.index') }}')
                        ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
                        : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-50'"
                   :title="sidebarIconOnly ? 'Transport Logs' : ''">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span x-show="!sidebarIconOnly" x-cloak class="transition-opacity duration-200 whitespace-nowrap">Transport Logs</span>
                </a>
                <a href="{{ route('settings.edit') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors relative"
                   :class="isActive('{{ route('settings.edit') }}')
                        ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
                        : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-50'"
                   :title="sidebarIconOnly ? 'Settings' : ''">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span x-show="!sidebarIconOnly" x-cloak class="transition-opacity duration-200 whitespace-nowrap">Settings</span>
                </a>
            </div>
        </div>

        @can('manage-admins')
            <div>
                <span x-show="!sidebarIconOnly" x-cloak class="px-3 text-xxs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 transition-opacity duration-200">Administration</span>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors relative"
                       :class="isActive('{{ route('users.index') }}')
                            ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
                            : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-50'"
                       :title="sidebarIconOnly ? 'Users' : ''">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span x-show="!sidebarIconOnly" x-cloak class="transition-opacity duration-200 whitespace-nowrap">Users</span>
                    </a>
                    <a href="{{ route('activity-logs.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors relative"
                       :class="isActive('{{ route('activity-logs.index') }}')
                            ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50'
                            : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-50'"
                       :title="sidebarIconOnly ? 'Activity Logs' : ''">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span x-show="!sidebarIconOnly" x-cloak class="transition-opacity duration-200 whitespace-nowrap">Activity Logs</span>
                    </a>
                </div>
            </div>
        @endcan
    </nav>

    <div class="border-t border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                {{ auth()->user()?->initials() ?? 'U' }}
            </div>
            <div x-show="!sidebarIconOnly" x-cloak class="min-w-0 flex-1 transition-opacity duration-200">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()?->name ?? 'Guest' }}</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()?->email ?? 'guest@transport.app' }}</p>
            </div>
        </div>
    </div>
</aside>
