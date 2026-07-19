@props(['label', 'current', 'previous', 'previousLabel', 'currency' => 'đ', 'inverted' => false, 'format' => 'money'])

@php
    $diff = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    $isUp = $diff > 0;
    $isBad = $inverted ? $isUp : !$isUp;
@endphp

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">
        @if($format === 'money')
            {{ number_format($current, 0, ',', '.') }} {{ $currency }}
        @elseif($format === 'kwh')
            {{ number_format($current, 1, ',', '.') }} kWh
        @else
            {{ $current }}{{ $currency }}
        @endif
    </p>
    @if ($diff !== null)
        <div class="flex items-center gap-1 mt-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                {{ $isBad ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                           'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                {{ $isUp ? '↑' : '↓' }} {{ abs($diff) }}%
            </span>
            <span class="text-xs text-slate-400 dark:text-slate-500">vs {{ $previousLabel }}</span>
        </div>
    @else
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Chưa có dữ liệu so sánh</p>
    @endif
</div>
