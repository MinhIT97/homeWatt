<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.title') }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('budgets.index') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">🎯 Hạn mức</a>
                <a href="{{ route('transfers.index') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">🔁 Chuyển ví</a>
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

            @include('expense::partials.quick-entry', ['quickHomes' => $homes, 'quickSelectedHomeId' => $quickSelectedHomeId])

            <!-- Filters Header -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-8">
                <form method="GET" action="{{ route('expenses.index') }}" class="space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Period Selector Tabs -->
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

                        <!-- Custom value inputs based on selected period -->
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
                    </div>

                    <!-- Dropdown Filters (Home, Wallet, Category, Type) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-4 border-t border-slate-200/50">
                        <div>
                            <select name="home_id" onchange="this.form.submit()" class="w-full bg-white/80 border border-slate-300 rounded-xl pl-3 pr-10 py-2 text-xs text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm">
                                <option value="">-- Tất cả nhà --</option>
                                @foreach($homes as $h)
                                    <option value="{{ $h->id }}" @selected(request('home_id') == $h->id)>{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="wallet_id" onchange="this.form.submit()" class="w-full bg-white/80 border border-slate-300 rounded-xl pl-3 pr-10 py-2 text-xs text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm">
                                <option value="">-- Tất cả ví --</option>
                                @foreach($wallets as $w)
                                    <option value="{{ $w->id }}" @selected(request('wallet_id') == $w->id)>{{ $w->name }} ({{ $w->home?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="category_id" onchange="this.form.submit()" class="w-full bg-white/80 border border-slate-300 rounded-xl pl-3 pr-10 py-2 text-xs text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm">
                                <option value="">-- Tất cả danh mục --</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->type === 'income' ? '📥' : '📤' }} {{ $c->name }} ({{ $c->home?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="type" onchange="this.form.submit()" class="w-full bg-white/80 border border-slate-300 rounded-xl pl-3 pr-10 py-2 text-xs text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition shadow-sm">
                                <option value="">-- Tất cả loại --</option>
                                <option value="expense" @selected(request('type') == 'expense')>Chi tiêu</option>
                                <option value="income" @selected(request('type') == 'income')>Thu nhập</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistics Panel -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Income Card -->
                <div class="glass-panel rounded-2xl border border-green-200/60 shadow-sm bg-green-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-green-600 uppercase tracking-wider">Đã thu (Thu nhập)</span>
                        <h4 class="text-2xl font-extrabold text-green-700 font-outfit mt-1">
                            +{{ number_format($totalIncome, 0, ',', '.') }} VND
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
                            -{{ number_format($totalSpent, 0, ',', '.') }} VND
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-650 text-2xl">
                        📉
                    </div>
                </div>

                <div class="glass-panel rounded-2xl border border-teal-200/60 shadow-sm bg-teal-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-teal-600 uppercase tracking-wider">Thu nợ / Đi vay</span>
                        <h4 class="text-2xl font-extrabold text-teal-700 font-outfit mt-1">
                            +{{ number_format($totalDebtIn, 0, ',', '.') }} VND
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 text-2xl">
                        🤝
                    </div>
                </div>

                <div class="glass-panel rounded-2xl border border-amber-200/60 shadow-sm bg-amber-50/20 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Cho vay / Trả nợ</span>
                        <h4 class="text-2xl font-extrabold text-amber-700 font-outfit mt-1">
                            -{{ number_format($totalDebtOut, 0, ',', '.') }} VND
                        </h4>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-2xl">
                        💸
                    </div>
                </div>
            </div>

            @if($expenses->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-12 text-center">
                    <div class="text-5xl mb-4">💸</div>
                    <h3 class="text-lg font-bold mb-2">{{ __('expense.no_expenses') }}</h3>
                    <p class="text-slate-500 text-sm">{{ __('expense.no_expenses_desc') }}</p>
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
                                @foreach($transactions as $e)
                                    @php
                                        $group = $e->category?->category_group;
                                        $isDebtFlow = in_array($group, \Modules\Expense\Models\ExpenseCategory::DEBT_GROUPS, true);
                                    @endphp
                                    <div class="flex items-center justify-between p-4 bg-white/60 hover:bg-white rounded-xl border {{ $isDebtFlow ? 'border-amber-200/80' : 'border-slate-200/60' }} shadow-sm transition hover:shadow-md group">
                                        <a href="{{ route('expenses.show', $e) }}" class="flex items-center gap-3 flex-1 min-w-0">
                                            <span class="text-xl w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shadow-sm shrink-0">{{ $e->category?->icon ?? '📝' }}</span>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-800 text-sm truncate">{{ $e->description ?: $e->category?->name }}</div>
                                                <div class="text-[11px] text-slate-550">{{ $e->occurred_at?->format('H:i') }} · {{ $e->wallet?->name }} · {{ $e->category?->name }} @if($isDebtFlow) · Vay nợ @endif</div>
                                            </div>
                                        </a>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <div class="font-bold text-sm {{ $e->isIncome() ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $e->isIncome() ? '+' : '-' }}{{ number_format((float) $e->amount, 0, ',', '.') }} {{ $e->currency ?? 'VND' }}
                                            </div>
                                            <a href="{{ route('expenses.edit', $e) }}" class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary-600" title="Chỉnh sửa">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
