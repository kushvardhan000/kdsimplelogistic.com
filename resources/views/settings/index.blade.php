@extends('layouts.app')

@section('title', 'Settings · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Settings' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Settings</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure your workspace preferences and appearance.</p>
    </div>

    <div class="w-full space-y-6">
        <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2" method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PATCH')

            <div>
                <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Profile</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Update your personal information.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input name="name" label="Full name" :value="auth()->user()?->name" required />
                @if(auth()->user()->isSuperAdmin())
                    <x-ui.input name="email" label="Email address" type="email" :value="auth()->user()?->email" required />
                @endif
            </div>

            @if(auth()->user()->isSuperAdmin())
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="current_password" label="Current Password" type="password" placeholder="Required to change password" />
                    <x-ui.input name="password" label="New Password" type="password" placeholder="Leave blank to keep" />
                    <x-ui.input name="password_confirmation" label="Confirm New Password" type="password" placeholder="Repeat new password" />
                </div>
            @endif

            <div class="flex justify-end">
                <x-ui.button type="submit" variant="brand" size="md">
                    Save changes
                </x-ui.button>
            </div>
        </form>

        <!-- <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Appearance</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Choose how the dashboard looks.</p>
                </div>

                <div class="mt-4 flex gap-3">
                    <button type="button" @click="$store.theme.set(false)"
                        class="flex flex-1 flex-col items-center gap-2 rounded-lg border-2 p-3 text-sm font-medium transition-colors"
                        :class="!$store.theme.isDark ? 'border-brand-500 text-zinc-900 dark:text-zinc-50' : 'border-zinc-200 text-zinc-500 dark:border-zinc-700'">
                        <span class="h-10 w-full rounded bg-zinc-100"></span>
                        Light
                    </button>
                    <button type="button" @click="$store.theme.set(true)"
                        class="flex flex-1 flex-col items-center gap-2 rounded-lg border-2 p-3 text-sm font-medium transition-colors"
                        :class="$store.theme.isDark ? 'border-brand-500 text-zinc-900 dark:text-zinc-50' : 'border-zinc-200 text-zinc-500 dark:border-zinc-700'">
                        <span class="h-10 w-full rounded bg-zinc-800"></span>
                        Dark
                    </button>
                </div>
            </div>

            @if(app()->environment('local', 'testing'))
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div>
                        <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">System Information</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Server and application details.</p>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Laravel Version</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ app()->version() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">PHP Version</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ phpversion() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Timezone</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ config('app.timezone') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Environment</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ app()->environment() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Debug Mode</span>
                            @php
                                $debug = config('app.debug');
                                $badgeClass = $debug
                                    ? 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-500/20'
                                    : 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/20';
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClass }}">
                                {{ $debug ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div> -->
    </div>
@endsection
