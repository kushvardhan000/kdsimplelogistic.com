@props([
    'branches' => collect(),
])

<x-ui.modal id="add-branch-modal" title="Add New Branch" size="sm">
    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <button type="button" @click="$dispatch('close-modal', 'add-branch-modal')" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Cancel
            </button>
            <button type="submit" form="add-branch-form" :disabled="submitting" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Save
            </button>
        </div>
    </x-slot>

    <div x-data="inlineCreate({
        url: '{{ route('entities.branches.store') }}',
        modalId: 'add-branch-modal',
        entityType: 'branch',
        targetSelect: '[name=\"branch_id\"]'
    })">
        <form id="add-branch-form" @submit.prevent="submit($event)">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="branch_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="branch_name" required maxlength="255"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <template x-if="fieldError('name')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('name')"></p></template>
                </div>

                <div>
                    <label for="branch_code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="branch_code" required maxlength="20"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Short alphanumeric code (e.g. DEL, MUM)</p>
                    <template x-if="fieldError('code')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('code')"></p></template>
                </div>

                <div>
                    <label for="branch_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                    <textarea name="address" id="branch_address" rows="2"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"></textarea>
                    <template x-if="fieldError('address')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('address')"></p></template>
                </div>

                <template x-if="fieldError('_form')">
                    <p class="text-xs text-red-600 dark:text-red-400" x-text="fieldError('_form')"></p>
                </template>
            </div>
        </form>
    </div>
</x-ui.modal>

<x-ui.modal id="add-fuel-station-modal" title="Add New Fuel Station" size="md">
    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <button type="button" @click="$dispatch('close-modal', 'add-fuel-station-modal')" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Cancel
            </button>
            <button type="submit" form="add-fuel-station-form" :disabled="submitting" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Save
            </button>
        </div>
    </x-slot>

    <div x-data="inlineCreate({
        url: '{{ route('entities.fuel-stations.store') }}',
        modalId: 'add-fuel-station-modal',
        entityType: 'fuel_station',
        targetSelect: '#linked_fuel_station_id'
    })">
        <form id="add-fuel-station-form" @submit.prevent="submit($event)">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="fs_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="fs_name" required maxlength="255"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <template x-if="fieldError('name')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('name')"></p></template>
                </div>

                <div>
                    <label for="fs_branch_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Branch</label>
                    <select name="branch_id" id="fs_branch_id"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select branch (optional)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                    <div class="mt-1 flex items-center gap-1">
                        <button type="button" @click="$dispatch('open-modal', 'add-branch-modal')"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">+ Add New Branch</button>
                    </div>
                    <template x-if="fieldError('branch_id')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('branch_id')"></p></template>
                </div>

                <div>
                    <label for="fs_contact_info" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contact Info</label>
                    <input type="text" name="contact_info" id="fs_contact_info" maxlength="255"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <template x-if="fieldError('contact_info')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('contact_info')"></p></template>
                </div>

                <div>
                    <label for="fs_address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                    <textarea name="address" id="fs_address" rows="2"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"></textarea>
                    <template x-if="fieldError('address')"><p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('address')"></p></template>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="fs_is_active" value="1" checked
                        class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                    <label for="fs_is_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</label>
                </div>

                <template x-if="fieldError('_form')">
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="fieldError('_form')"></p>
                </template>
            </div>
        </form>
    </div>
</x-ui.modal>
