@extends('layouts.app')

@section('title', 'Edit User · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Users' => route('users.index'), 'Edit' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Edit User</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Update account details for {{ $user->name }}.</p>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user])
    </form>
@endsection
