@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id ?? $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 disabled:opacity-50 sm:text-sm']) }}
    >

    @error($name)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
