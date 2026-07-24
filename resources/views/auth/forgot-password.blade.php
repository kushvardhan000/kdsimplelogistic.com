@extends('layouts.auth')

@section('title', 'Forgot Password · Transport')

@section('content')
    <h1 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Forgot your password?</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Enter your email and we'll send a reset link.</p>

    @if(session('status'))
        <p class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">{{ session('status') }}</p>
    @endif

    <form class="mt-6 space-y-4" method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-ui.input name="email" label="Email" type="email" placeholder="you@transport.app" :value="old('email', $email ?? null)" required />
        <x-ui.button type="submit" variant="brand" class="w-full">Send Reset Link</x-ui.button>
    </form>

    <a href="{{ route('login') }}" class="mt-4 block text-center text-sm text-zinc-500 hover:underline dark:text-zinc-400">Back to login</a>
@endsection
