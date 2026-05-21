@php
    $role = $role ?? 'user';
    $roleLabels = [
        'super-admin' => 'مدیر کل',
        'client-admin' => 'مدیر کارفرما',
        'user' => 'کاربر',
    ];
    $statusLabels = [
        'draft' => 'پیش‌نویس',
        'processing' => 'در حال پردازش',
        'ready' => 'آماده',
        'failed' => 'ناموفق',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">داشبورد</h2>
            <span class="rounded-full bg-brand-100 px-3 py-1 text-sm font-medium text-brand-800">
                {{ $roleLabels[$role] ?? 'کاربر' }}
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- نمایش یک‌باره‌ی رمز ساخته‌شده هنگام ثبت‌نام --}}
            @if (session('generated_password'))
                <div class="rounded-2xl bg-brand-50 border border-brand-200 p-6"
                     x-data="{ shown: false, copied: false }">
                    <div class="flex items-start gap-4">
                        <div class="text-3xl">🔐</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-brand-900 mb-2">
                                @if (session('generated_password_source') === 'phone')
                                    رمز شما = شماره موبایل
                                @else
                                    رمز خودکار ساخته شد
                                @endif
                            </h3>
                            <p class="text-sm text-brand-800 mb-4">
                                این رمز را یادداشت کنید — یک‌بار نمایش داده می‌شود:
                            </p>
                            <div class="flex items-center gap-3">
                                <code class="flex-1 rounded-lg bg-white px-4 py-3 font-mono text-lg text-ink-900 select-all"
                                      dir="ltr"
                                      x-text="shown ? '{{ session('generated_password') }}' : '••••••••'"></code>
                                <button type="button"
                                        x-on:click="shown = !shown"
                                        class="rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600 transition">
                                    <span x-text="shown ? 'مخفی' : 'نمایش'"></span>
                                </button>
                                <button type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ session('generated_password') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="rounded-lg border border-brand-300 px-4 py-3 text-sm font-medium text-brand-700 hover:bg-brand-100 transition">
                                    <span x-text="copied ? '✓ کپی شد' : 'کپی'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- خوش‌آمد --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">
                        سلام، {{ auth()->user()->name }} 👋
                    </h3>
                    <p class="text-gray-600">
                        خوش آمدی به Rangify — پلتفرم رنگ‌آمیزی مجازی دیوار.
                    </p>
                </div>
            </div>

            {{-- ===================== مدیر کل ===================== --}}
            @if ($role === 'super-admin')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ([
                        ['label' => 'کارفرماها', 'value' => $stats['tenants'], 'icon' => '🏢'],
                        ['label' => 'کاربران', 'value' => $stats['users'], 'icon' => '👥'],
                        ['label' => 'پروژه‌ها', 'value' => $stats['projects'], 'icon' => '🖼️'],
                    ] as $card)
                        <div class="bg-white rounded-2xl shadow-sm p-6 flex items-center gap-4">
                            <div class="text-3xl">{{ $card['icon'] }}</div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</div>
                                <div class="text-sm text-gray-500">{{ $card['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">کارفرماهای اخیر</h3>
                    </div>
                    @if ($tenants->isEmpty())
                        <p class="p-6 text-sm text-gray-500">هنوز کارفرمایی ثبت نشده است.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-right font-medium">نام</th>
                                    <th class="px-6 py-3 text-right font-medium">کاربران</th>
                                    <th class="px-6 py-3 text-right font-medium">پروژه‌ها</th>
                                    <th class="px-6 py-3 text-right font-medium">وضعیت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($tenants as $tenant)
                                    <tr>
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $tenant->name }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ number_format($tenant->users_count) }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ number_format($tenant->projects_count) }}</td>
                                        <td class="px-6 py-3">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $tenant->status === 'active' ? 'فعال' : 'معلق' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            {{-- ===================== مدیر کارفرما ===================== --}}
            @elseif ($role === 'client-admin')
                @unless ($tenant)
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 text-sm text-amber-800">
                        حساب شما هنوز به هیچ کارفرمایی متصل نیست. با مدیر کل تماس بگیرید.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white rounded-2xl shadow-sm p-6 flex items-center gap-4">
                            <div class="text-3xl">👥</div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['members']) }}</div>
                                <div class="text-sm text-gray-500">اعضای تیم</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm p-6 flex items-center gap-4">
                            <div class="text-3xl">🖼️</div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['projects']) }}</div>
                                <div class="text-sm text-gray-500">پروژه‌های {{ $tenant->name }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">اعضای تیم</h3>
                        </div>
                        @if ($members->isEmpty())
                            <p class="p-6 text-sm text-gray-500">هنوز عضوی اضافه نشده است.</p>
                        @else
                            <ul class="divide-y divide-gray-100">
                                @foreach ($members as $member)
                                    <li class="px-6 py-3 flex items-center justify-between">
                                        <span class="font-medium text-gray-900">{{ $member->name }}</span>
                                        <span class="text-sm text-gray-500" dir="ltr">
                                            {{ $member->email ?? $member->phone }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endunless

            {{-- ===================== کاربر عادی ===================== --}}
            @else
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">پروژه‌های من</h3>
                        <a href="{{ route('trial') }}"
                           class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                            + پروژه جدید
                        </a>
                    </div>
                    @if ($projects->isEmpty())
                        <div class="p-10 text-center">
                            <div class="text-4xl mb-3">🎨</div>
                            <p class="text-gray-600 mb-1">هنوز پروژه‌ای نساخته‌اید.</p>
                            <p class="text-sm text-gray-400">
                                یک عکس از اتاق آپلود کنید و رنگ‌آمیزی را شروع کنید.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                            @foreach ($projects as $project)
                                <div class="rounded-xl border border-gray-100 overflow-hidden">
                                    <div class="aspect-video bg-gray-100">
                                        @if ($project->thumbnail_path)
                                            <img src="{{ asset($project->thumbnail_path) }}"
                                                 alt="{{ $project->title }}"
                                                 class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="p-3">
                                        <div class="font-medium text-gray-900 truncate">{{ $project->title }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $statusLabels[$project->status] ?? $project->status }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
