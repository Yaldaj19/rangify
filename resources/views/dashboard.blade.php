@php
    $role = $role ?? 'user';
    $roleLabels = [
        'super-admin' => __('Super Admin'),
        'client-admin' => __('Client Admin'),
        'user' => __('User'),
    ];
    $statusLabels = [
        'draft' => __('Draft'),
        'processing' => __('Processing'),
        'ready' => __('Ready'),
        'failed' => __('Failed'),
    ];
@endphp

<x-admin-layout :title="__('Dashboard')">
    <x-slot:actions>
        <span class="rounded-full bg-brand-100 px-3 py-1 text-sm font-medium text-brand-800">
            {{ $roleLabels[$role] ?? __('User') }}
        </span>
    </x-slot:actions>

    <div class="space-y-6">

        {{-- خوش‌آمد --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="p-6">
                <h3 class="mb-1 text-xl font-bold text-gray-900">
                    {{ __('Hello, :name 👋', ['name' => auth()->user()->name]) }}
                </h3>
                <p class="text-gray-600">{{ __('Welcome to Rangify — the virtual wall painting platform.') }}</p>
            </div>
        </div>

        {{-- ===================== super-admin ===================== --}}
        @if ($role === 'super-admin')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ([
                    ['label' => __('Tenants'), 'value' => $stats['tenants'], 'icon' => '🏢'],
                    ['label' => __('Users'), 'value' => $stats['users'], 'icon' => '👥'],
                    ['label' => __('Projects'), 'value' => $stats['projects'], 'icon' => '🖼️'],
                ] as $card)
                    <div class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm">
                        <div class="text-3xl">{{ $card['icon'] }}</div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</div>
                            <div class="text-sm text-gray-500">{{ $card['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Recent Tenants') }}</h3>
                </div>
                @if ($tenants->isEmpty())
                    <p class="p-6 text-sm text-gray-500">{{ __('No tenants yet.') }}</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-start font-medium">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-start font-medium">{{ __('Users') }}</th>
                                <th class="px-6 py-3 text-start font-medium">{{ __('Projects') }}</th>
                                <th class="px-6 py-3 text-start font-medium">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($tenants as $tenant)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $tenant->name }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ number_format($tenant->users_count) }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ number_format($tenant->projects_count) }}</td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tenant->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $tenant->status === 'active' ? __('Active') : __('Suspended') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        {{-- ===================== client-admin ===================== --}}
        @elseif ($role === 'client-admin')
            @unless ($tenant)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                    {{ __("Your account isn't linked to any organization yet. Please contact the administrator.") }}
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm">
                        <div class="text-3xl">👥</div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['members']) }}</div>
                            <div class="text-sm text-gray-500">{{ __('Team Members') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm">
                        <div class="text-3xl">🖼️</div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['projects']) }}</div>
                            <div class="text-sm text-gray-500">{{ __('Projects') }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Team Members') }}</h3>
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('Manage') }}</a>
                    </div>
                    @if ($members->isEmpty())
                        <p class="p-6 text-sm text-gray-500">{{ __('No members yet.') }}</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($members as $member)
                                <li class="flex items-center justify-between px-6 py-3">
                                    <span class="font-medium text-gray-900">{{ $member->name }}</span>
                                    <span class="text-sm text-gray-500" dir="ltr">{{ $member->email ?? $member->phone }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endunless

        {{-- ===================== user ===================== --}}
        @else
            @php $me = auth()->user(); $remaining = $me->remainingEdits(); @endphp
            <div class="flex items-center justify-between rounded-2xl bg-white p-6 shadow-sm">
                <div>
                    <div class="mb-1 text-sm text-gray-500">{{ __('Image Edit Quota') }}</div>
                    @if ($remaining === null)
                        <div class="text-2xl font-bold text-emerald-600">{{ __('Unlimited') }}</div>
                    @else
                        <div class="text-2xl font-bold text-gray-900">
                            {{ $remaining }}
                            <span class="text-base font-normal text-gray-400">{{ __('remaining of :total', ['total' => $me->edit_quota]) }}</span>
                        </div>
                    @endif
                </div>
                @if ($remaining === null || $remaining > 0)
                    <a href="{{ route('trial') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-600">{{ __('New Project') }}</a>
                @else
                    <span class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-400">{{ __('Quota exhausted') }}</span>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('My Projects') }}</h3>
                </div>
                @if ($projects->isEmpty())
                    <div class="p-10 text-center">
                        <div class="mb-3 text-4xl">🎨</div>
                        <p class="mb-1 text-gray-600">{{ __("You haven't created any projects yet.") }}</p>
                        <p class="text-sm text-gray-400">{{ __('Upload a room photo and start painting.') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($projects as $project)
                            <div class="overflow-hidden rounded-xl border border-gray-100">
                                <div class="aspect-video bg-gray-100">
                                    @if ($project->thumbnail_path)
                                        <img src="{{ asset($project->thumbnail_path) }}" alt="{{ $project->title }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="p-3">
                                    <div class="truncate font-medium text-gray-900">{{ $project->title }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $statusLabels[$project->status] ?? $project->status }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>
</x-admin-layout>
