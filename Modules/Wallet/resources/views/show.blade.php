<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight flex items-center gap-2">
                    @if($wallet->icon)<span>{{ $wallet->icon }}</span>@endif
                    {{ $wallet->name }}
                </h2>
                <p class="text-sm text-slate-500">{{ $wallet->home->name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('expenses.create', ['wallet_id' => $wallet->id, 'home_id' => $wallet->home_id]) }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white shadow-sm transition">+ Thêm giao dịch</a>
                <a href="{{ route('transfers.create', ['from_wallet_id' => $wallet->id, 'home_id' => $wallet->home_id]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 shadow-sm transition">Chuyển ví</a>
                <div class="w-px h-8 bg-slate-200/60 self-center hidden sm:block"></div>
                <a href="{{ route('wallets.edit', $wallet) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('wallets.archive', $wallet) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-50 border border-amber-200 hover:border-amber-300 rounded-xl text-sm font-semibold text-amber-700 hover:bg-amber-100 shadow-sm transition">{{ __('wallet.archive') }}</button>
                </form>
                <form method="POST" action="{{ route('wallets.destroy', $wallet) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-50 border border-red-200 hover:border-red-300 rounded-xl text-sm font-semibold text-red-700 hover:bg-red-100 shadow-sm transition">{{ __('common.delete') }}</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50/80 border border-red-200 text-red-700 rounded-xl text-sm font-medium shadow-sm">{{ session('error') }}</div>
            @endif

            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-8">
                <dl class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.current_balance') }}</dt>
                        <dd class="mt-1 text-3xl font-extrabold text-primary-600 font-outfit">{{ number_format($currentBalance, 0, ',', '.') }} {{ $wallet->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.type_label') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ __('wallet.type_'.$wallet->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.opening_balance') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ number_format($wallet->opening_balance, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('common.status') }}</dt>
                        <dd class="mt-1">
                            @if($wallet->is_archived)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-amber-50 text-amber-700 border-amber-200">{{ __('wallet.archived') }}</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200">{{ __('common.active') }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if($wallet->description)
                    <div class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-600">{{ $wallet->description }}</div>
                @endif
            </div>

            <h3 class="text-lg font-bold text-slate-800 font-outfit mb-4">{{ __('wallet.recent_transactions') }}</h3>
            @if($recentExpenses->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-8 text-center">
                    <p class="text-slate-500 text-sm">{{ __('wallet.no_transactions') }}</p>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        @foreach($recentExpenses as $expense)
                            <li class="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl w-8 h-8 rounded-lg bg-slate-50 border flex items-center justify-center shadow-sm">{{ $expense['icon'] }}</span>
                                    <div>
                                        <div class="font-semibold text-slate-800 text-sm">{{ $expense['description'] }}</div>
                                        <div class="text-[11px] text-slate-550">{{ $expense['occurred_at']?->format('d/m/Y H:i') }} · {{ $expense['category_name'] }}</div>
                                    </div>
                                </div>
                                <div class="font-bold text-sm {{ $expense['type'] === 'income' ? 'text-green-600' : 'text-red-650' }}">
                                    {{ $expense['type'] === 'income' ? '+' : '-' }}{{ number_format($expense['amount'], 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>