<button {{ $attributes->merge(['type' => 'submit', 'class' => 'rg-gradient inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:ring-offset-1 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
