@extends('layouts.app')

@section('title', 'Custom Field Options · Settings')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Settings' => route('settings.edit'), 'Custom Field Options' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Custom Field Options</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage dropdown options for payment modes and payment plans used in account transactions.</p>
    </div>

    <div class="space-y-6">
        @foreach($fields as $fieldKey => $fieldLabel)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">{{ $fieldLabel }}</h2>
                    <button type="button" @click="$dispatch('open-modal', 'custom-field-create-{{ $fieldKey }}')" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                        + Add Option
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">Label</th>
                                <th class="px-4 py-3 font-medium">Value</th>
                                <th class="px-4 py-3 font-medium">Sort Order</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($optionsByField->get($fieldKey, collect()) as $option)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $option->label }}</td>
                                    <td class="px-4 py-3 font-mono text-zinc-700 dark:text-zinc-300">{{ $option->value }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $option->sort_order }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $option->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            {{ $option->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                    @click="$dispatch('open-modal', 'custom-field-edit-{{ $fieldKey }}-{{ $option->id }}')"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                                    title="Edit">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('settings.custom-fields.destroy', $option) }}" class="inline" onsubmit="return confirm('Deactivate this option? It will no longer be available for new transactions.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Deactivate">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.108 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        No options defined for {{ strtolower($fieldLabel) }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Create Modal --}}
            <x-ui.modal id="custom-field-create-{{ $fieldKey }}" title="Add {{ $fieldLabel }} Option" size="sm">
                <div x-data="inlineCreate({
                    url: '{{ route('settings.custom-fields.store') }}',
                    modalId: 'custom-field-create-{{ $fieldKey }}',
                    entityType: 'custom_field_option',
                })">
                    <form @submit.prevent="submit($event)">
                        @csrf
                        <input type="hidden" name="field_key" value="{{ $fieldKey }}">
                        <div class="space-y-4">
                            <div>
                                <label for="cf_label_{{ $fieldKey }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Label <span class="text-red-500">*</span></label>
                                <input type="text" name="label" id="cf_label_{{ $fieldKey }}" required maxlength="255"
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                <template x-if="fieldError('label')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('label')"></p></template>
                            </div>

                            <div>
                                <label for="cf_value_{{ $fieldKey }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Value <span class="text-red-500">*</span></label>
                                <input type="text" name="value" id="cf_value_{{ $fieldKey }}" required maxlength="255"
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lowercase, underscores only (e.g. bank_transfer)</p>
                                <template x-if="fieldError('value')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('value')"></p></template>
                            </div>

                            <div>
                                <label for="cf_sort_{{ $fieldKey }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort Order</label>
                                <input type="number" name="sort_order" id="cf_sort_{{ $fieldKey }}" min="0"
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                <template x-if="fieldError('sort_order')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('sort_order')"></p></template>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" id="cf_active_{{ $fieldKey }}" value="1" checked
                                    class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                                <label for="cf_active_{{ $fieldKey }}" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</label>
                            </div>

                            <template x-if="fieldError('_form')">
                                <p class="text-xs text-red-600 dark:text-red-400" x-text="fieldError('_form')"></p>
                            </template>
                        </div>
                    </form>
                </div>
            </x-ui.modal>

            {{-- Edit Modal --}}
            @foreach($optionsByField->get($fieldKey, collect()) as $option)
                <x-ui.modal id="custom-field-edit-{{ $fieldKey }}-{{ $option->id }}" title="Edit {{ $fieldLabel }} Option" size="sm">
                    <div x-data="inlineCreate({
                        url: '{{ route('settings.custom-fields.update', $option) }}',
                        modalId: 'custom-field-edit-{{ $fieldKey }}-{{ $option->id }}',
                        entityType: 'custom_field_option',
                    })">
                        <form @submit.prevent="submit($event)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="field_key" value="{{ $fieldKey }}">
                            <div class="space-y-4">
                                <div>
                                    <label for="cf_edit_label_{{ $option->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Label <span class="text-red-500">*</span></label>
                                    <input type="text" name="label" id="cf_edit_label_{{ $option->id }}" required maxlength="255" value="{{ $option->label }}"
                                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                    <template x-if="fieldError('label')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('label')"></p></template>
                                </div>

                                <div>
                                    <label for="cf_edit_value_{{ $option->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Value <span class="text-red-500">*</span></label>
                                    <input type="text" name="value" id="cf_edit_value_{{ $option->id }}" required maxlength="255" value="{{ $option->value }}"
                                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lowercase, underscores only</p>
                                    <template x-if="fieldError('value')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('value')"></p></template>
                                </div>

                                <div>
                                    <label for="cf_edit_sort_{{ $option->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort Order</label>
                                    <input type="number" name="sort_order" id="cf_edit_sort_{{ $option->id }}" min="0" value="{{ $option->sort_order }}"
                                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                    <template x-if="fieldError('sort_order')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('sort_order')"></p></template>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" id="cf_edit_active_{{ $option->id }}" value="1" {{ $option->is_active ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                                    <label for="cf_edit_active_{{ $option->id }}" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</label>
                                </div>

                                <template x-if="fieldError('_form')">
                                    <p class="text-xs text-red-600 dark:text-red-400" x-text="fieldError('_form')"></p>
                                </template>
                            </div>
                        </form>
                    </div>
                </x-ui.modal>
            @endforeach
        @endforeach
    </div>
@endsection