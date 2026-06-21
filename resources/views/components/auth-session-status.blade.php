@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600 bg-green-50/80 border border-green-200 rounded-xl px-4 py-3 shadow-sm']) }}>
        {{ $status }}
    </div>
@endif
