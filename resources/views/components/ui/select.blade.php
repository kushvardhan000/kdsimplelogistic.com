@props([
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id ?? $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 disabled:opacity-50 sm:text-sm']) }}
    >
        {{ $slot }}
    </select>

    @error($name)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
