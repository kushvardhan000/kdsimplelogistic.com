<div class="grid gap-4 sm:grid-cols-2">
    <x-ui.input name="name" label="Full name" placeholder="Jane Doe" :value="old('name', $user?->name)" required />
    <x-ui.input name="email" label="Email address" type="email" placeholder="you@transport.app" :value="old('email', $user?->email)" required />

    <x-ui.input name="password" label="Password" type="password" placeholder="Leave blank to keep current" :required="!$user" />
    <x-ui.input name="password_confirmation" label="Confirm Password" type="password" placeholder="Repeat password" :required="!$user" />

    <div class="space-y-1.5">
        <label for="role" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
        <select name="role" id="role" class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
            <option value="admin" @selected(old('role', $user?->role) === 'admin')>Admin</option>
            <option value="super_admin" @selected(old('role', $user?->role) === 'super_admin')>Super Admin</option>
        </select>
        @error('role')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</span>
        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true)) class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
            Account is active
        </label>
    </div>
</div>

<div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
    <a href="{{ route('users.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
        Cancel
    </a>
    <x-ui.button type="submit" variant="brand" size="md">
        {{ $user ? 'Update User' : 'Create User' }}
    </x-ui.button>
</div>
