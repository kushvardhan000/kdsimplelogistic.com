@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'createUrl' => '',
    'createModalId' => '',
    'createModalTitle' => 'Add New',
    'createFormFields' => [],
    'fallbackUrl' => '',
    'syncName' => '',
    'entityType' => '',
    'selected' => null,
    'placeholder' => 'Select...',
    'required' => false,
    'searchable' => true,
    'allowClear' => true,
    'error' => null,
])

@php
    $id = $id ?? 'creatable-select-' . uniqid();
    $modalId = $createModalId ?: $id . '-create-modal';
    $fieldName = $name ?: $id;
    $entityType = $entityType ?: (str_contains($createUrl, '/entities/branches') ? 'branch' : (str_contains($createUrl, '/entities/fuel-stations') ? 'fuel_station' : 'creatable_select'));
    $options = is_string($options) ? (json_decode($options, true) ?: []) : $options;
    $createFormFields = is_string($createFormFields) ? (json_decode($createFormFields, true) ?: []) : $createFormFields;
    $errorBag = $errors->has($fieldName) ? $errors->getBag('default') : null;
    $errorMessage = $errorBag && $errorBag->has($fieldName) ? $errorBag->first($fieldName) : ($error ?? null);
    $config = [
        'name' => $fieldName,
        'options' => $options,
        'selected' => $selected,
        'searchable' => (bool) $searchable,
        'allowClear' => (bool) $allowClear,
        'createUrl' => $createUrl,
        'fallbackUrl' => $fallbackUrl,
        'syncName' => $syncName,
        'entityType' => $entityType,
        'createModalId' => $modalId,
        'createFormFields' => $createFormFields,
    ];
@endphp

<div x-data='creatableSelect(@json($config))'>
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    <div class="relative mt-1.5" @click.outside="open = false">
        <div class="relative">
            <input
                type="text"
                x-model="query"
                @click="open = true; filter()"
                @input="filter()"
                @focus="open = true; filter()"
                @keydown.escape="open = false"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="selectHighlighted()"
                :placeholder="{{ $placeholder }}"
                autocomplete="off"
                class="block w-full rounded-lg border-zinc-300 bg-white text-zinc-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm py-2 pl-3 pr-10 cursor-pointer"
                :class="{ 'border-red-300 ring-red-200 focus:border-red-500 focus:ring-red-500': errorMessage }"
                x-ref="input"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <input type="hidden" :name="name" :value="selectedValue">

        @if($searchable)
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed z-40 max-h-60 overflow-y-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-premium-lg dark:border-zinc-700 dark:bg-zinc-800"
            :style="{ top: dropdownTop + 'px', left: dropdownLeft + 'px', width: dropdownWidth + 'px' }"
        >
            <template x-for="(option, idx) in filteredOptions" :key="option.value">
                <button
                    type="button"
                    @click="select(option)"
                    @mouseenter="highlighted = idx"
                    :class="highlighted === idx
                        ? 'bg-zinc-100 dark:bg-zinc-700'
                        : 'bg-transparent'"
                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition-colors"
                >
                    <span class="min-w-0 truncate font-medium text-zinc-900 dark:text-zinc-50" x-text="option.label"></span>
                </button>
            </template>

            <div x-show="filteredOptions.length === 0 && createUrl" class="px-3 py-3 text-sm text-zinc-600 dark:text-zinc-300 border-t border-zinc-200 dark:border-zinc-700">
                <p>No matching option found.</p>
                <a
                    href="{{ $fallbackUrl ?: $createUrl }}"
                    :href="managementUrl"
                    target="_blank"
                    @click.prevent="open = false; $dispatch('open-modal', createModalId)"
                    class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300 dark:hover:text-brand-200"
                >
                    + Add <span class="font-mono" x-text="query || 'new'"></span> →
                </a>
            </div>

            <div x-show="filteredOptions.length === 0 && !createUrl" class="px-3 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                <p>No matching option found.</p>
            </div>
        </div>
        @endif

        <div class="mt-1.5 flex items-center justify-between text-xs">
            <p class="text-zinc-500 dark:text-zinc-400">
                <span x-show="!selectedValue">No option selected
                    @if($createUrl)
                         — <a :href="managementUrl" target="_blank" @click.prevent="open = false; $dispatch('open-modal', createModalId)" class="text-brand-600 hover:text-brand-700 dark:text-brand-400">create one</a>
                    @endif
                </span>
                <span x-show="selectedValue">
                    Selected: <span class="font-medium" x-text="selectedLabel"></span>
                    <span x-show="selectedBalance !== null"> · Current balance:
                        <span :class="selectedBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" class="font-medium" x-text="formatBalance()"></span>
                    </span>
                </span>
            </p>
            <button
                type="button"
                x-show="selectedValue && allowClear"
                @click="clear()"
                class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            >
                Clear
            </button>
        </div>

        @if($errorMessage)
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
        @endif
    </div>

    {{-- Create Modal --}}
    @if($createUrl && $createFormFields)
    <x-ui.modal :id="$modalId" :title="$createModalTitle" size="md">
        <div x-data="inlineCreate({
            url: '{{ $createUrl }}',
            modalId: '{{ $modalId }}',
            entityType: '{{ $entityType }}',
        })">
            <form id="{{ $modalId }}-form" @submit.prevent="submit($event)">
                @csrf
                <div class="space-y-4">
                    @foreach($createFormFields as $field)
                        <div>
                            <label for="{{ $modalId }}-{{ $field['name'] }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false) <span class="text-red-500">*</span> @endif
                            </label>
                            @if(isset($field['type']) && $field['type'] === 'select')
                                <select name="{{ $field['name'] }}" id="{{ $modalId }}-{{ $field['name'] }}"
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                                    @if($field['required'] ?? false) required @endif>
                                    <option value="">Select...</option>
                                    @foreach($field['options'] as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif(isset($field['type']) && $field['type'] === 'textarea')
                                <textarea name="{{ $field['name'] }}" id="{{ $modalId }}-{{ $field['name'] }}" rows="{{ $field['rows'] ?? 2 }}"
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"></textarea>
                            @elseif(isset($field['type']) && $field['type'] === 'checkbox')
                                <div class="mt-1.5 flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field['name'] }}" id="{{ $modalId }}-{{ $field['name'] }}" value="1" @if($field['checked'] ?? false) checked @endif
                                        class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                                    <label for="{{ $modalId }}-{{ $field['name'] }}" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $field['label'] }}</label>
                                </div>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" id="{{ $modalId }}-{{ $field['name'] }}"
                                    @if($field['required'] ?? false) required @endif
                                    @if(isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                    @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                    @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                    class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                            @endif
                            @if(isset($field['help']))
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $field['help'] }}</p>
                            @endif
                            <template x-if="fieldError('{{ $field['name'] }}')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('{{ $field['name'] }}')"></p></template>
                        </div>
                    @endforeach

                    <template x-if="fieldError('_form')">
                        <p class="text-xs text-red-600 dark:text-red-400" x-text="fieldError('_form')"></p>
                    </template>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', '{{ $modalId }}')" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                    Cancel
                </button>
                <button type="submit" form="{{ $modalId }}-form" :disabled="submitting" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Save
                </button>
            </div>
        </x-slot>
    </x-ui.modal>
    @endif

</div>