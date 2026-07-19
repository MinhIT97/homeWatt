<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 dark:text-slate-100 font-outfit leading-tight">Công nợ</h2>
    </x-slot>

    @php
        $statusClasses = [
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'partial' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'settled' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        ];
        $statusLabels = [
            'pending' => 'Chưa trả',
            'partial' => 'Trả một phần',
            'settled' => 'Đã trả',
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- Home Selector --}}
            <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex-1">
                        <x-input-label for="home_id" :value="__('Chọn nhà')" />
                        <select id="home_id" name="home_id"
                            onchange="window.location.href = '{{ route('debts.index') }}?home_id=' + this.value"
                            class="mt-1 block w-full bg-white/80 dark:bg-slate-700/80 border border-slate-300 dark:border-slate-600 rounded-xl shadow-sm text-slate-800 dark:text-slate-200 py-2.5 px-3.5">
                            @foreach ($homes as $home)
                                <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>
                                    {{ $home->name }} ({{ $home->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if (!$selectedHomeId)
                <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400 text-lg font-semibold">Chọn một nhà để xem công nợ</p>
                </div>
            @else
                {{-- Summary Section --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 p-6">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Bạn đang nợ</p>
                        <p class="text-2xl font-extrabold text-red-600 dark:text-red-400 mt-1">
                            {{ number_format($owesTotal, 0, ',', '.') }} đ
                        </p>
                    </div>
                    <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 p-6">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Nợ bạn</p>
                        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($owedToYouTotal, 0, ',', '.') }} đ
                        </p>
                    </div>
                    <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 p-6">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Số dư ròng</p>
                        <p class="text-2xl font-extrabold mt-1 {{ $netBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 0, ',', '.') }} đ
                        </p>
                    </div>
                </div>

                {{-- You Owe Section --}}
                <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Bạn đang nợ</h3>
                    </div>
                    @if ($debts['owes']->isEmpty())
                        <div class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                            <p class="font-semibold">Bạn không nợ ai cả!</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach ($debts['owes'] as $split)
                                <div class="px-6 py-4 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($split->payer->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                {{ $split->payer->name }}
                                            </p>
                                            @if ($split->expense)
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                    {{ $split->expense->description ?: __('expense.no_description') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                            {{ number_format($split->remaining(), 0, ',', '.') }} đ
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $statusClasses[$split->status] ?? '' }}">
                                            {{ $statusLabels[$split->status] ?? $split->status }}
                                        </span>
                                        @if (!$split->isSettled())
                                            <form method="POST" action="{{ route('debts.settle', $split) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    onclick="return confirm('Xác nhận đã trả khoản nợ này?')"
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition shadow-sm">
                                                    Đã trả
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Owed to You Section --}}
                <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Nợ bạn</h3>
                    </div>
                    @if ($debts['owed_to_you']->isEmpty())
                        <div class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">
                            <p class="font-semibold">Không ai nợ bạn cả!</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach ($debts['owed_to_you'] as $split)
                                <div class="px-6 py-4 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($split->ower->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                {{ $split->ower->name }}
                                            </p>
                                            @if ($split->expense)
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                    {{ $split->expense->description ?: __('expense.no_description') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ number_format($split->remaining(), 0, ',', '.') }} đ
                                        </span>
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $statusClasses[$split->status] ?? '' }}">
                                            {{ $statusLabels[$split->status] ?? $split->status }}
                                        </span>
                                        @if (!$split->isSettled())
                                            <form method="POST" action="{{ route('debts.settle', $split) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    onclick="return confirm('Xác nhận đã nhận được thanh toán?')"
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition shadow-sm">
                                                    Đã nhận
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Home Debt Summary --}}
                @if (count($summary) > 0)
                    <div class="glass-panel rounded-2xl border border-slate-200/60 dark:border-slate-700/50 bg-white/70 dark:bg-slate-800/70 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Tổng quan công nợ nhà</h3>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach ($summary as $entry)
                                <div class="px-6 py-4 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($entry['user']->name ?? '?', 0, 1)) }}
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                            {{ $entry['user']->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm shrink-0">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                                            Nợ: {{ number_format($entry['total_owed_to_you'], 0, ',', '.') }} đ
                                        </span>
                                        <span class="text-red-600 dark:text-red-400 font-bold">
                                            Nợ bạn: {{ number_format($entry['total_owes'], 0, ',', '.') }} đ
                                        </span>
                                        <span class="font-extrabold {{ $entry['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            Ròng: {{ $entry['net'] >= 0 ? '+' : '' }}{{ number_format($entry['net'], 0, ',', '.') }} đ
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
