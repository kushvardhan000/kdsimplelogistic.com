@extends('layouts.auth')

@section('title', 'Reset Password · Transport')

@section('content')
    <h1 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Reset password</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Choose a new password for your account.</p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-ui.input name="email" label="Email" type="email" placeholder="you@transport.app" :value="old('email', $email ?? null)" required />
        <x-ui.input name="password" label="New Password" type="password" required />
        <x-ui.input name="password_confirmation" label="Confirm Password" type="password" required />
        <x-ui.button type="submit" variant="brand" class="w-full">Reset Password</x-ui.button>
    </form>

    <a href="{{ route('login') }}" class="mt-4 block text-center text-sm text-zinc-500 hover:underline dark:text-zinc-400">Back to login</a>
@endsection
