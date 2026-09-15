@props([
    'title' => null,
])

<header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-zinc-200 bg-zinc-50/80 px-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80 sm:px-6 lg:px-8">
    <button
        type="button"
        @click="toggleSidebar()"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800 md:hidden"
        aria-label="Toggle sidebar"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <button
        type="button"
        @click="toggleSidebar()"
        class="hidden h-9 w-9 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800 md:flex"
        aria-label="Toggle sidebar"
        title="Toggle sidebar"
    >
        <svg x-show="!sidebarIconOnly" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 7l-7 7 7 7"/></svg>
        <svg x-show="sidebarIconOnly" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
    </button>

    <h1 class="truncate text-base font-semibold text-zinc-950 dark:text-zinc-50">{{ $title ?? (isset($slot) && $slot->isNotEmpty() ? $slot : 'Transport') }}</h1>

    <form
    method="GET"
    action="{{ route('transport-logs.index') }}"
    class="hidden flex-1 justify-center px-4 md:flex lg:px-6"
    x-data="{
        value: '{{ addslashes(request('search')) }}',
        submit() {
            const v = this.value.trim();
            if (v.startsWith('TL-') || /^TL-\d{6}$/.test(v)) {
                window.location.href = '/trace/' + encodeURIComponent(v);
            } else {
                this.$el.submit();
            }
        }
    }"
    @submit.prevent="submit()"
>
    <div class="relative w-full max-w-xl xl:max-w-2xl">
        <input
            type="text"
            name="search"
            placeholder="Search transport logs or paste a trace code..."
            x-model="value"
            class="h-10 w-full rounded-xl border border-zinc-300 bg-white pl-10 pr-4 text-sm shadow-sm transition-all duration-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
        />

        <svg
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</form>

    <div class="ml-auto flex items-center gap-2 md:ml-0">
        <button
            type="button"
            @click="$store.theme.toggle()"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800"
            aria-label="Toggle theme"
        >
            <svg x-show="!$store.theme.isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg x-show="$store.theme.isDark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <div class="relative" x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white"
                aria-label="User menu"
            >
                {{ auth()->user()?->initials() ?? 'U' }}
            </button>

            <div
                 x-show="open"
                 x-cloak
                 @click.away="open = false"
                 class="absolute right-0 z-40 mt-2 w-48 rounded-xl border border-zinc-200 bg-white py-1 shadow-premium-lg dark:border-zinc-800 dark:bg-zinc-900"
             >
                <div class="border-b border-zinc-200 px-4 py-2 dark:border-zinc-800">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()?->name ?? 'Guest' }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()?->email ?? '' }}</p>
                </div>
                <a href="{{ route('settings.edit') }}" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-zinc-50 dark:text-red-400 dark:hover:bg-zinc-800">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
