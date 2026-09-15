<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased" :class="{ 'dark': $store.theme.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Transport'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 text-zinc-900 transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-50">
    <div class="flex min-h-screen overflow-x-hidden" x-data="layout()" x-init="init()">
        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-zinc-950/50 md:hidden" @click="sidebarOpen = false" x-transition.opacity></div>

        <x-layout.sidebar />

        <div class="flex flex-1 flex-col overflow-x-hidden transition-all duration-300 ease-out-expo"
             :class="isDesktop ? (sidebarIconOnly ? 'md:ml-16' : 'md:ml-64') : ''">
            <x-layout.header />

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    @include('components.ui.toast')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
