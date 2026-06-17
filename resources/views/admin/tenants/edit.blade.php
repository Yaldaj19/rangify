<x-admin-layout title="{{ __('Edit Tenant') }}">
    @php
        $input = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-colors duration-150';
        $label = 'block text-sm font-medium text-ink-900 mb-1.5';
        $err = 'mt-1.5 text-xs text-red-500';
    @endphp

    <div class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="{{ $label }}">{{ __('Tenant Name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $tenant->name) }}" class="{{ $input }}">
                @error('name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="status" class="{{ $label }}">{{ __('Status') }}</label>
                <select id="status" name="status" class="{{ $input }}">
                    <option value="active" @selected(old('status', $tenant->status) === 'active')>{{ __('Active') }}</option>
                    <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>{{ __('Suspended') }}</option>
                </select>
                @error('status') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <label class="flex cursor-pointer select-none items-center gap-3">
                    <input type="hidden" name="tool_access" value="0">
                    <input type="checkbox" name="tool_access" value="1" @checked(old('tool_access', $tenant->tool_access))
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/30">
                    <span class="text-sm font-medium text-ink-900">{{ __('Tool access (unlimited use of the editor)') }}</span>
                </label>
                <p class="mt-1.5 ps-7 text-xs text-gray-400">{{ __('When enabled, this organization can use the color editor without limits.') }}</p>
            </div>

            @if ($tenant->owner)
                <p class="text-xs text-gray-400">{{ __('Tenant admin: :name', ['name' => $tenant->owner->name]) }}</p>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
                    {{ __('Save Changes') }}
                </button>
                <a href="{{ route('admin.tenants.index') }}"
                   class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-ink-900">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
