<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:opacity-50 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
