@props(['user'])

@php
    $base = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';
@endphp

@if ($user->hasUnlimitedQuota())
    <span class="{{ $base }} bg-emerald-50 text-emerald-700">{{ __('Unlimited') }}</span>
@else
    @php
        $quota = (int) $user->edit_quota;
        $used = $user->usedEdits();
        $remaining = max(0, $quota - $used);
        $color = match (true) {
            $remaining === 0 => 'bg-red-50 text-red-600',
            $quota > 0 && $remaining <= max(1, (int) ceil($quota * 0.2)) => 'bg-amber-50 text-amber-700',
            default => 'bg-sky-50 text-sky-700',
        };
    @endphp
    <span class="{{ $base }} {{ $color }}" title="{{ __(':used of :total used', ['used' => $used, 'total' => $quota]) }}">
        {{ __(':used of :total', ['used' => $used, 'total' => $quota]) }}
    </span>
@endif
