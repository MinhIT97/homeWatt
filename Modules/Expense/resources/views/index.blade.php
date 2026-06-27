<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.title') }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('reports.monthly') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('expense.report_monthly') }}</a>
                <a href="{{ route('expenses.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white">{{ __('expense.add_new') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(! auth()->user()->telegram_chat_id)
                <div class="mb-6 p-5 bg-gradient-to-r from-blue-50 to-indigo-50/50 border border-blue-150 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl p-2 bg-white rounded-xl shadow-sm">🤖</span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Ghi chép giao dịch nhanh qua Telegram</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Liên kết tài khoản của bạn với Telegram Bot để nhập nhanh các giao dịch bằng cú pháp thông minh.</p>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}#telegram-section" class="shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        Kết nối ngay
                    </a>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
            @endif

            @if($expenses->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-12 text-center">
                    <div class="text-5xl mb-4">💸</div>
                    <h3 class="text-lg font-bold mb-2">{{ __('expense.no_expenses') }}</h3>
                    <p class="text-slate-500 text-sm">{{ __('expense.no_expenses_desc') }}</p>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        @foreach($expenses as $e)
                            <li class="p-4 flex items-center justify-between hover:bg-slate-50">
                                <a href="{{ route('expenses.show', $e) }}" class="flex items-center gap-3 flex-1">
                                    <span class="text-xl">{{ $e->category?->icon ?? '📝' }}</span>
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $e->description ?: $e->category?->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $e->occurred_at?->format('d/m/Y') }} · {{ $e->wallet?->name }} · {{ $e->category?->name }}</div>
                                    </div>
                                </a>
                                <div class="font-bold {{ $e->isIncome() ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $e->isIncome() ? '+' : '-' }}{{ number_format((float) $e->amount, 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mt-6">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>