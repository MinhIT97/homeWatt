<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-white/85 border border-slate-300 hover:border-slate-400 rounded-xl font-semibold text-sm text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

