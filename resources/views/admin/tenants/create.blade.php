<x-admin-layout title="{{ __('New Tenant') }}">
    @php
        $input = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-ink-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-colors duration-150';
        $label = 'block text-sm font-medium text-ink-900 mb-1.5';
        $err = 'mt-1.5 text-xs text-red-500';
    @endphp

    <div class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="{{ $label }}">{{ __('Tenant Name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="{{ $input }}" placeholder="{{ __('e.g. Alpha Inc.') }}">
                @error('name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="mb-4 text-sm font-semibold text-ink-900">{{ __('Client Admin') }}</p>

                <div class="space-y-5">
                    <div>
                        <label for="admin_name" class="{{ $label }}">{{ __('Admin Name') }}</label>
                        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" class="{{ $input }}">
                        @error('admin_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin_email" class="{{ $label }}">{{ __('Admin Email') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" class="{{ $input }}" dir="ltr">
                        @error('admin_email') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin_phone" class="{{ $label }}">{{ __('Admin Phone') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                        <input type="tel" id="admin_phone" name="admin_phone" value="{{ old('admin_phone') }}" class="{{ $input }}" dir="ltr" placeholder="09123456789">
                        @error('admin_phone') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin_password" class="{{ $label }}">{{ __('Admin Password') }}
                            <span class="text-xs font-normal text-gray-400">{{ __('(empty = auto-generated)') }}</span>
                        </label>
                        <input type="text" id="admin_password" name="admin_password" class="{{ $input }}" autocomplete="new-password">
                        @error('admin_password') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
                    {{ __('Create Tenant') }}
                </button>
                <a href="{{ route('admin.tenants.index') }}"
                   class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-ink-900">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
