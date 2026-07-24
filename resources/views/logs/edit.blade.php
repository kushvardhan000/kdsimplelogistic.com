@extends('layouts.app')

@section('title', 'Edit Transport Log · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Transport Logs' => route('transport-logs.index'), 'Edit' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Edit Transport Log</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Update the transport transaction details.</p>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        @include('logs._form', ['log' => $transportLog, 'formAction' => route('transport-logs.update', $transportLog), 'formMethod' => 'PUT'])
    </div>
@endsection
