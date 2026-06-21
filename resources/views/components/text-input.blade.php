@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white/80 border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 rounded-xl shadow-sm text-slate-800 placeholder-slate-400 transition duration-150']) }}>


