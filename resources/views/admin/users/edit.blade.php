<x-admin-layout title="{{ __('Edit User') }}">
    @php
        $input = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-ink-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-colors duration-150';
        $label = 'block text-sm font-medium text-ink-900 mb-1.5';
        $err = 'mt-1.5 text-xs text-red-500';
        $isUnlimited = old('unlimited', $user->edit_quota === null ? 1 : 0);
    @endphp

    <div class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5"
              x-data="{ unlimited: {{ $isUnlimited ? 'true' : 'false' }} }">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="{{ $label }}">{{ __('Full Name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="{{ $input }}">
                @error('name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="{{ $label }}">{{ __('Email') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $input }}" dir="ltr">
                @error('email') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="{{ $label }}">{{ __('Phone Number') }} <span class="text-xs font-normal text-gray-400">{{ __('(optional)') }}</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $input }}" dir="ltr">
                @error('phone') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="{{ $label }}">{{ __('New Password') }}
                    <span class="text-xs font-normal text-gray-400">{{ __('(empty = no change)') }}</span>
                </label>
                <input type="text" id="password" name="password" class="{{ $input }}" autocomplete="new-password">
                @error('password') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('Image Edit Quota') }}</label>
                <div class="flex items-center gap-3">
                    <input type="hidden" name="unlimited" :value="unlimited ? 1 : 0">
                    <input type="number" name="edit_quota" min="0" value="{{ old('edit_quota', $user->edit_quota ?? 2) }}"
                           class="{{ $input }} w-28 disabled:cursor-not-allowed disabled:opacity-40"
                           :disabled="unlimited">
                    <label class="flex cursor-pointer select-none items-center gap-2">
                        <input type="checkbox" x-model="unlimited"
                               class="h-4 w-4 cursor-pointer rounded border-gray-300 text-brand-500 focus:ring-brand-500/30">
                        <span class="text-sm text-gray-600">{{ __('Unlimited') }}</span>
                    </label>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">{{ __('Has created :count images so far.', ['count' => $user->usedEdits()]) }}</p>
                @error('edit_quota') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
                    {{ __('Save Changes') }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-ink-900">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
