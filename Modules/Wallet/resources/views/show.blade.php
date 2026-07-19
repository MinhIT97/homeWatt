<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight flex items-center gap-3">
                    @if($wallet->icon)
                        <x-wallet-icon :icon="$wallet->icon" :color="$wallet->color" class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" />
                    @endif
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
                <dl class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.current_balance') }}</dt>
                        <dd class="mt-1 text-3xl font-extrabold text-primary-600 font-outfit">{{ number_format($currentBalance, 0, ',', '.') }} {{ $wallet->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.total_spent') }}</dt>
                        <dd class="mt-1 text-3xl font-extrabold text-red-500 font-outfit">{{ number_format($totalSpentAllTime, 0, ',', '.') }} {{ $wallet->currency }}</dd>
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

            <!-- Filters Header -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-8">
                <form method="GET" action="{{ route('wallets.show', $wallet) }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" name="period" value="all" 
                                class="px-4 py-2 text-sm font-semibold rounded-xl transition duration-150 {{ $period === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Tất cả
                        </button>
                        <button type="submit" name="period" value="day" 
                                class="px-4 py-2 text-sm font-semibold rounded-xl transition duration-150 {{ $period === 'day' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Theo Ngày
                        </button>
                        <button type="submit" name="period" value="month" 
                                class="px-4 py-2 text-sm font-semibold rounded-xl transition duration-150 {{ $period === 'month' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Theo Tháng
                        </button>
                        <button type="submit" name="period" value="year" 
                                class="px-4 py-2 text-sm font-semibold rounded-xl transition duration-150 {{ $period === 'year' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Theo Năm
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($period === 'day')
                            <div class="flex items-center gap-2">
                                <label for="date" class="text-xs font-semibold text-slate-500">Chọn ngày:</label>
                                <input type="date" id="date" name="date" value="{{ $dateVal }}" onchange="this.form.submit()" 
                                       class="bg-white/80 border border-slate-300 rounded-xl px-3 py-1.5 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm" />
                            </div>
                        @elseif($period === 'month')
                            <div class="flex items-center gap-2">
                                <label for="month" class="text-xs font-semibold text-slate-500">Chọn tháng:</label>
                                <input type="month" id="month" name="month" value="{{ $monthVal }}" onchange="this.form.submit()" 
                                       class="bg-white/80 border border-slate-300 rounded-xl px-3 py-1.5 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm" />
                            </div>
                        @elseif($period === 'year')
                            <div class="flex items-center gap-2">
                                <label for="year" class="text-xs font-semibold text-slate-500">Chọn năm:</label>
                                <select id="year" name="year" onchange="this.form.submit()" 
                                        class="bg-white/80 border border-slate-300 rounded-xl pl-3 pr-10 py-1.5 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm">
                                    @for($y = now()->year; $y >= now()->year - 10; $y--)
                                        <option value="{{ $y }}" @selected($y === $yearVal)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Statistics Panel -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <!-- Total Income Card -->
                <div class="glass-panel rounded-2xl border border-green-200/60 shadow-sm bg-green-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-green-600 uppercase tracking-wider">Đã thu (Thu nhập)</span>
                        <h4 class="text-2xl font-extrabold text-green-700 font-outfit mt-1">
                            +{{ number_format($totalIncome, 0, ',', '.') }} {{ $wallet->currency }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-2xl">
                        📈
                    </div>
                </div>

                <!-- Total Spent Card -->
                <div class="glass-panel rounded-2xl border border-red-200/60 shadow-sm bg-red-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-red-500 uppercase tracking-wider">Đã chi (Chi tiêu)</span>
                        <h4 class="text-2xl font-extrabold text-red-650 font-outfit mt-1">
                            -{{ number_format($totalSpent, 0, ',', '.') }} {{ $wallet->currency }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-650 text-2xl">
                        📉
                    </div>
                </div>

                <!-- Transfer In Card -->
                <div class="glass-panel rounded-2xl border border-blue-200/60 shadow-sm bg-blue-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Chuyển vào ví</span>
                        <h4 class="text-2xl font-extrabold text-blue-700 font-outfit mt-1">
                            +{{ number_format($totalTransferIn, 0, ',', '.') }} {{ $wallet->currency }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl">
                        📥
                    </div>
                </div>

                <!-- Transfer Out Card -->
                <div class="glass-panel rounded-2xl border border-slate-200/80 shadow-sm bg-slate-50/50 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Chuyển khỏi ví</span>
                        <h4 class="text-2xl font-extrabold text-slate-700 font-outfit mt-1">
                            -{{ number_format($totalTransferOut, 0, ',', '.') }} {{ $wallet->currency }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-2xl">
                        📤
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 font-outfit mb-6">{{ __('wallet.recent_transactions') }}</h3>

            @if($groupedExpenses->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-8 text-center">
                    <p class="text-slate-500 text-sm">{{ __('wallet.no_transactions') }}</p>
                </div>
            @else
                @php
                    $today = now()->format('Y-m-d');
                    $yesterday = now()->subDay()->format('Y-m-d');
                    $vietnameseDays = [
                        'Monday' => 'Thứ Hai',
                        'Tuesday' => 'Thứ Ba',
                        'Wednesday' => 'Thứ Tư',
                        'Thursday' => 'Thứ Năm',
                        'Friday' => 'Thứ Sáu',
                        'Saturday' => 'Thứ Bảy',
                        'Sunday' => 'Chủ Nhật'
                    ];
                @endphp

                <div class="relative pl-8">
                    <!-- Vertical timeline line -->
                    <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-slate-200"></div>

                    @foreach($groupedExpenses as $date => $transactions)
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                            $englishDay = $carbonDate->format('l');
                            $vnDay = $vietnameseDays[$englishDay] ?? $englishDay;

                            // Calculate daily totals
                            $dailyIncome = $transactions->where('type', 'income')->sum('amount');
                            $dailyExpense = $transactions->where('type', 'expense')->sum('amount');
                            $dailyNet = $dailyIncome - $dailyExpense;
                        @endphp
                        <div class="relative mb-8 last:mb-0">
                            <!-- Timeline node icon -->
                            <div class="absolute -left-8 top-1 w-8 h-8 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center shadow-sm z-10">
                                <span class="text-xs font-bold text-slate-500">{{ $carbonDate->format('d') }}</span>
                            </div>

                            <!-- Date Header with daily totals -->
                            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                <h4 class="text-sm font-bold text-slate-800 font-outfit">
                                    @if($date === $today)
                                        Hôm nay - {{ $carbonDate->format('d/m/Y') }}
                                    @elseif($date === $yesterday)
                                        Hôm qua - {{ $carbonDate->format('d/m/Y') }}
                                    @else
                                        {{ $vnDay }}, {{ $carbonDate->format('d/m/Y') }}
                                    @endif
                                </h4>
                                <div class="flex items-center gap-3 text-xs font-semibold">
                                    @if($dailyIncome > 0)
                                        <span class="text-green-600">+{{ number_format($dailyIncome, 0, ',', '.') }}</span>
                                    @endif
                                    @if($dailyExpense > 0)
                                        <span class="text-red-600">-{{ number_format($dailyExpense, 0, ',', '.') }}</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-lg text-[11px] font-extrabold {{ $dailyNet >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $dailyNet >= 0 ? '+' : '' }}{{ number_format($dailyNet, 0, ',', '.') }} đ
                                    </span>
                                </div>
                            </div>

                            <!-- Transactions list -->
                            <div class="space-y-3">
                                @foreach($transactions as $expense)
                                    @php
                                        $isTransfer = str_starts_with($expense['type'], 'transfer_');
                                        $isPositive = $expense['type'] === 'income' || $expense['type'] === 'transfer_in';
                                        $amountClass = $isTransfer
                                            ? 'text-blue-600'
                                            : ($expense['type'] === 'income' ? 'text-green-600' : 'text-red-600');
                                    @endphp
                                    <div class="flex items-center justify-between p-4 bg-white/60 hover:bg-white rounded-xl border border-slate-200/60 shadow-sm transition hover:shadow-md group">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-xl w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shadow-sm shrink-0">{{ $expense['icon'] }}</span>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-800 text-sm truncate">{{ $expense['description'] }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $expense['occurred_at']?->format('H:i') }} · {{ $expense['category_name'] }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <div class="font-bold text-sm {{ $amountClass }}">
                                                {{ $isPositive ? '+' : '-' }}{{ number_format($expense['amount'], 0, ',', '.') }} {{ $wallet->currency }}
                                            </div>
                                            @if(!$isTransfer && isset($expense['id']))
                                                <a href="{{ route('expenses.edit', $expense['id']) }}" class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary-600" title="Chỉnh sửa">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
