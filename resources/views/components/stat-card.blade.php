@props(['label', 'value', 'icon' => '📊', 'format' => 'money', 'currency' => 'đ', 'trend' => null, 'trendLabel' => null, 'href' => null])

@php
    $Tag = $href ? 'a' : 'div';
@endphp

<{{ $Tag }}
    @if($href) href="{{ $href }}" @endif
    class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 {{ $href ? 'hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-md transition cursor-pointer' : '' }}">
    <div class="flex items-center gap-2 mb-2">
        <span class="text-lg">{{ $icon }}</span>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
    </div>
    <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
        @if($format === 'money')
            {{ number_format($value, 0, ',', '.') }} {{ $currency }}
        @elseif($format === 'kwh')
            {{ number_format($value, 1, ',', '.') }} kWh
        @elseif($format === 'number')
            {{ number_format($value) }}
        @else
            {{ $value }}{{ $currency }}
        @endif
    </p>
    @if($trend !== null)
        <div class="flex items-center gap-1 mt-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                {{ $trend > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $trend > 0 ? '+' : '' }}{{ $trend }}%
            </span>
            @if($trendLabel)
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $trendLabel }}</span>
            @endif
        </div>
    @endif
</{{ $Tag }}>
