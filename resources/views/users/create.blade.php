@extends('layouts.app')

@section('title', 'Invite User · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Users' => route('users.index'), 'Invite' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Invite User</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Create a new administrator account.</p>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        @csrf
        @include('users._form', ['user' => null])
    </form>
@endsection
