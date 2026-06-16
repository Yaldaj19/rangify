@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-ink-900 mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
