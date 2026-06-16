<x-admin-layout title="{{ __('New User') }}">
    @php
        $input = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-ink-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-colors duration-150';
        $label = 'block text-sm font-medium text-ink-900 mb-1.5';
        $err = 'mt-1.5 text-xs text-red-500';
    @endphp

    <div class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5"
              x-data="{ unlimited: {{ old('unlimited') ? 'true' : 'false' }} }">
            @csrf

            <div>
                <label for="name" class="{{ $label }}">{{ __('Full Name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="{{ $input }}" placeholder="{{ __('e.g. John Doe') }}">
                @error('name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            @if ($isSuperAdmin)
                <div>
                    <label for="tenant_id" class="{{ $label }}">{{ __('Tenant') }}</label>
                    <select id="tenant_id" name="tenant_id" class="{{ $input }}">
                        <option value="">{{ __('— Select tenant —') }}</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                    @error('tenant_id') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    @if ($tenants->isEmpty())
                        <p class="mt-1.5 text-xs text-amber-600">{{ __('You must create a tenant first.') }}</p>
                    @endif
                </div>
            @endif

            <div>
                <label for="email" class="{{ $label }}">{{ __('Email') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="{{ $input }}" dir="ltr" placeholder="user@example.com">
                @error('email') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="{{ $label }}">{{ __('Phone Number') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="{{ $input }}" dir="ltr" placeholder="09123456789">
                @error('phone') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="{{ $label }}">{{ __('Password') }}
                    <span class="text-xs font-normal text-gray-400">{{ __('(empty = auto-generated password)') }}</span>
                </label>
                <input type="text" id="password" name="password" class="{{ $input }}" autocomplete="new-password" placeholder="{{ __('At least 8 characters') }}">
                @error('password') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('Image Edit Quota') }}</label>
                <div class="flex items-center gap-3">
                    <input type="hidden" name="unlimited" :value="unlimited ? 1 : 0">
                    <input type="number" name="edit_quota" min="0" value="{{ old('edit_quota', 2) }}"
                           class="{{ $input }} w-28 disabled:cursor-not-allowed disabled:opacity-40"
                           :disabled="unlimited" placeholder="{{ __('e.g. 5') }}">
                    <label class="flex cursor-pointer select-none items-center gap-2">
                        <input type="checkbox" x-model="unlimited"
                               class="h-4 w-4 cursor-pointer rounded border-gray-300 text-brand-500 focus:ring-brand-500/30">
                        <span class="text-sm text-gray-600">{{ __('Unlimited') }}</span>
                    </label>
                </div>
                @error('edit_quota') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
                    {{ __('Save User') }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-ink-900">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
