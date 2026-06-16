@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-ink-900 placeholder:text-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:bg-white transition-colors duration-150 disabled:opacity-50']) }}>
