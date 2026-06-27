<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.report_monthly') }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.category') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    {{ __('expense.report_category') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- Filter Form --}}
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                <form method="GET" action="{{ route('reports.monthly') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <x-input-label for="home_id" :value="__('expense.select_home')" />
                        <select id="home_id" name="home_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2 px-3 text-sm" required>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="month" :value="__('Tháng')" />
                        <select id="month" name="month" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2 px-3 text-sm" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($month == $m)>{{ __('Tháng') }} {{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year" :value="__('Năm')" />
                        <select id="year" name="year" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2 px-3 text-sm" required>
                            @for($y = now()->year - 5; $y <= now()->year + 5; $y++)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <x-primary-button class="w-full justify-center">{{ __('common.apply') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if($report)
                {{-- Stats Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('expense.report_total_income') }}</div>
                        <div class="mt-2 text-2xl font-extrabold text-green-650 font-outfit">{{ number_format($report['income'], 0, ',', '.') }} đ</div>
                    </div>
                    <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('expense.report_total_expense') }}</div>
                        <div class="mt-2 text-2xl font-extrabold text-red-600 font-outfit">{{ number_format($report['expense'], 0, ',', '.') }} đ</div>
                    </div>
                    <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('expense.report_net') }}</div>
                        <div class="mt-2 text-2xl font-extrabold font-outfit {{ $report['net'] >= 0 ? 'text-green-650' : 'text-red-650' }}">
                            {{ $report['net'] >= 0 ? '+' : '' }}{{ number_format($report['net'], 0, ',', '.') }} đ
                        </div>
                    </div>
                    <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('expense.report_total_balance') }}</div>
                        <div class="mt-2 text-2xl font-extrabold text-primary-600 font-outfit">{{ number_format($report['total_balance'], 0, ',', '.') }} đ</div>
                    </div>
                </div>

                {{-- Báo cáo Vay & Cho vay --}}
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-extrabold text-slate-800 font-outfit text-base">📊 Báo cáo Vay & Cho vay</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/80">
                        {{-- Cho vay & Thu nợ --}}
                        <div class="p-5 rounded-2xl border border-slate-200/50 bg-slate-50/30 space-y-4">
                            <h4 class="font-bold text-slate-700 text-sm flex items-center gap-2">
                                <span>🤝</span> Cho vay & Thu hồi nợ
                            </h4>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã cho vay</div>
                                    <div class="mt-1 text-sm font-extrabold text-amber-600 font-outfit">
                                        {{ number_format($report['total_lent'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã thu hồi</div>
                                    <div class="mt-1 text-sm font-extrabold text-green-650 font-outfit">
                                        {{ number_format($report['total_collected'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Chưa thu hồi</div>
                                    <div class="mt-1 text-sm font-extrabold font-outfit {{ ($report['total_lent'] - $report['total_collected']) >= 0 ? 'text-amber-700' : 'text-green-700' }}">
                                        {{ number_format($report['total_lent'] - $report['total_collected'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Đi vay & Trả nợ --}}
                        <div class="p-5 rounded-2xl border border-slate-200/50 bg-slate-50/30 space-y-4">
                            <h4 class="font-bold text-slate-700 text-sm flex items-center gap-2">
                                <span>💸</span> Đi vay & Trả nợ
                            </h4>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã đi vay</div>
                                    <div class="mt-1 text-sm font-extrabold text-purple-650 font-outfit">
                                        {{ number_format($report['total_borrowed'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã trả nợ</div>
                                    <div class="mt-1 text-sm font-extrabold text-blue-650 font-outfit">
                                        {{ number_format($report['total_repaid'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                                <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Còn nợ</div>
                                    <div class="mt-1 text-sm font-extrabold font-outfit {{ ($report['total_borrowed'] - $report['total_repaid']) >= 0 ? 'text-purple-700' : 'text-blue-700' }}">
                                        {{ number_format($report['total_borrowed'] - $report['total_repaid'], 0, ',', '.') }} đ
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Daily Breakdown --}}
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-extrabold text-slate-800 font-outfit text-base">{{ __('Chi tiết theo ngày') }}</h3>
                    </div>
                    @if($report['income'] == 0 && $report['expense'] == 0)
                        <div class="p-8 text-center text-slate-500 text-sm bg-white/80">
                            {{ __('common.no_data') ?? 'Chưa có dữ liệu giao dịch trong tháng này.' }}
                        </div>
                    @else
                        <!-- Mobile View (Card List) -->
                        <div class="block sm:hidden divide-y divide-slate-100 bg-white/80">
                            @foreach($report['daily'] as $day)
                                @if($day['income'] > 0 || $day['expense'] > 0)
                                    <div class="p-4 space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-slate-800">
                                                {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold {{ ($day['income'] - $day['expense']) >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                                {{ ($day['income'] - $day['expense']) >= 0 ? '+' : '' }}{{ number_format($day['income'] - $day['expense'], 0, ',', '.') }} đ
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                            <div class="flex gap-1.5 items-center">
                                                <span class="text-slate-400">{{ __('expense.report_total_income') }}:</span>
                                                <span class="font-bold text-green-600">{{ $day['income'] > 0 ? '+' . number_format($day['income'], 0, ',', '.') . ' đ' : '0 đ' }}</span>
                                            </div>
                                            <div class="flex gap-1.5 items-center">
                                                <span class="text-slate-400">{{ __('expense.report_total_expense') }}:</span>
                                                <span class="font-bold text-red-600">{{ $day['expense'] > 0 ? '-' . number_format($day['expense'], 0, ',', '.') . ' đ' : '0 đ' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Desktop View (Table) -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/30">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.date') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('expense.report_total_income') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('expense.report_total_expense') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('expense.report_net') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/80 divide-y divide-slate-100">
                                    @foreach($report['daily'] as $day)
                                        @if($day['income'] > 0 || $day['expense'] > 0)
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800">
                                                    {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-650 font-bold">
                                                    {{ $day['income'] > 0 ? '+' . number_format($day['income'], 0, ',', '.') . ' đ' : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-650 font-bold">
                                                    {{ $day['expense'] > 0 ? '-' . number_format($day['expense'], 0, ',', '.') . ' đ' : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold {{ ($day['income'] - $day['expense']) >= 0 ? 'text-green-650' : 'text-red-650' }}">
                                                    {{ ($day['income'] - $day['expense']) >= 0 ? '+' : '' }}{{ number_format($day['income'] - $day['expense'], 0, ',', '.') }} đ
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
