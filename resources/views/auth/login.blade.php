@extends('layouts.auth')

@section('title', 'Sign in · Transport')

@section('content')
    <h1 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Sign in to your account</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Enter your credentials to continue.</p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('login') }}">
        @csrf

        <x-ui.input name="email" label="Email" type="email" placeholder="you@transport.app" required :value="old('email')" />
        <x-ui.input name="password" label="Password" type="password" placeholder="••••••••" required />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" name="remember" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                Remember me
            </label>
        </div>

        <x-ui.button type="submit" variant="brand" class="w-full" size="md">
            Sign in
        </x-ui.button>
    </form>
@endsection
