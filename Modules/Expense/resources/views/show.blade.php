<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.title') }} #{{ $expense->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="text-3xl">{{ $expense->category?->icon ?? '📝' }}</div>
                        <h3 class="text-xl font-bold text-slate-800 mt-2">{{ $expense->description ?: $expense->category?->name }}</h3>
                    </div>
                    <div class="text-3xl font-extrabold {{ $expense->isIncome() ? 'text-green-600' : 'text-red-600' }}">
                        {{ $expense->isIncome() ? '+' : '-' }}{{ number_format((float) $expense->amount, 0, ',', '.') }} {{ $expense->currency }}
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Type</dt><dd class="font-semibold">{{ $expense->isIncome() ? __('expense.type_income') : __('expense.type_expense') }}</dd></div>
                    <div><dt class="text-slate-500">Wallet</dt><dd class="font-semibold">{{ $expense->wallet?->name }}</dd></div>
                    <div><dt class="text-slate-500">Category</dt><dd class="font-semibold">{{ $expense->category?->name }}</dd></div>
                    <div><dt class="text-slate-500">Date</dt><dd class="font-semibold">{{ $expense->occurred_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>
                @if($expense->transfer_id)
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
                        {{ __('expense.transfer_title') }} #{{ $expense->transfer_id }}
                    </div>
                @endif
                <div class="flex gap-3 mt-6 pt-6 border-t border-slate-100">
                    @if(!$expense->belongsToTransfer())
                        <a href="{{ route('expenses.edit', $expense) }}" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-sm font-semibold">{{ __('common.edit') }}</a>
                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-semibold">{{ __('common.delete') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('expenses.index') }}" class="ml-auto px-4 py-2 text-sm text-slate-600">{{ __('common.back') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>