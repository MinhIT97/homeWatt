<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.transfer_title') }} #{{ $transfer->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="text-3xl">🔄</div>
                        <h3 class="text-xl font-bold text-slate-800 mt-2">{{ $transfer->description ?: __('expense.transfer_title') }}</h3>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-700">
                        {{ number_format((float) $transfer->amount, 0, ',', '.') }} {{ $transfer->currency }}
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">{{ __('expense.transfer_from') }}</dt><dd class="font-semibold text-slate-800">{{ $transfer->fromWallet?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('expense.transfer_to') }}</dt><dd class="font-semibold text-slate-800">{{ $transfer->toWallet?->name }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('expense.transfer_fee') }}</dt><dd class="font-semibold text-slate-800">{{ number_format((float) ($transfer->fee ?? 0), 0, ',', '.') }} {{ $transfer->currency }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('expense.occurred_at_label') }}</dt><dd class="font-semibold text-slate-800">{{ $transfer->occurred_at instanceof \Carbon\Carbon ? $transfer->occurred_at->format('d/m/Y H:i') : \Carbon\Carbon::parse($transfer->occurred_at)->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('common.created_by') ?? 'Người tạo' }}</dt><dd class="font-semibold text-slate-800">{{ $transfer->user?->name }}</dd></div>
                </dl>

                <div class="flex gap-3 mt-6 pt-6 border-t border-slate-100">
                    <form method="POST" action="{{ route('transfers.destroy', $transfer) }}" onsubmit="return confirm('{{ __('Are you sure you want to reverse this transfer?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-semibold hover:bg-red-100 transition">{{ __('common.delete') ?? 'Hoàn tác' }}</button>
                    </form>
                    <a href="{{ route('transfers.index') }}" class="ml-auto px-4 py-2 text-sm text-slate-600 font-semibold hover:text-slate-800 transition">{{ __('common.back') ?? 'Quay lại' }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
