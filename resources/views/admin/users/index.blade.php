<x-admin-layout title="{{ __('Users') }}">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}"
           class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
            {{ __('+ New User') }}
        </a>
    </x-slot:actions>

    @if ($users->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-2xl bg-white py-16 text-center shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-2xl">👥</div>
            <p class="text-sm font-medium text-ink-900">{{ __('No users yet') }}</p>
            <p class="text-sm text-gray-400">{{ __('Add your first user.') }}</p>
            <a href="{{ route('admin.users.create') }}"
               class="mt-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600">
                {{ __('Add User') }}
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Name') }}</th>
                            <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Email / Phone') }}</th>
                            @if ($isSuperAdmin)
                                <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Tenant') }}</th>
                                <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Role') }}</th>
                                <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Registered by') }}</th>
                            @endif
                            <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Quota') }}</th>
                            <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Projects') }}</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($users as $user)
                            <tr class="group transition-colors duration-100 hover:bg-gray-50/60">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-600">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </div>
                                        <span class="font-medium text-ink-900">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-500" dir="ltr">
                                    <div class="text-right">{{ $user->email ?? $user->phone ?? '—' }}</div>
                                </td>
                                @if ($isSuperAdmin)
                                    <td class="px-5 py-4 text-gray-600">{{ $user->tenant?->name ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        @php $role = $user->getRoleNames()->first(); @endphp
                                        @if ($role === 'super-admin')
                                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600">{{ __('Super Admin') }}</span>
                                        @elseif ($role === 'client-admin')
                                            <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">{{ __('Client Admin') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ __('User') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $user->creator?->name ?? '—' }}</td>
                                @endif
                                <td class="px-5 py-4"><x-quota-badge :user="$user" /></td>
                                <td class="px-5 py-4 text-gray-600">{{ number_format($user->projects_count) }}</td>
                                <td class="px-5 py-4">
                                    @canany(['update', 'delete'], $user)
                                        <div class="flex items-center gap-1 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                                            @can('update', $user)
                                                <a href="{{ route('admin.users.edit', $user) }}"
                                                   class="rounded-lg p-1.5 text-gray-400 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-600"
                                                   title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                                </a>
                                            @endcan
                                            @can('delete', $user)
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('{{ __('Delete user :name? This action cannot be undone.', ['name' => $user->name]) }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="rounded-lg p-1.5 text-gray-400 transition-colors duration-150 hover:bg-red-50 hover:text-red-600"
                                                            title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    @endcanany
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    @endif
</x-admin-layout>
