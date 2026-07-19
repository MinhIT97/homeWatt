<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 dark:text-slate-100 font-outfit leading-tight">{{ __('Tổng quan tất cả nhà') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Xem tổng hợp tài chính và năng lượng từ tất cả các nhà của bạn') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Tổng hợp tất cả --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">💰</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Tổng số dư') }}</p>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totals['total_balance'], 0, ',', '.') }} đ</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📈</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Thu nhập tháng') }}</p>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totals['monthly_income'], 0, ',', '.') }} đ</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📉</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Chi tiêu tháng') }}</p>
                </div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totals['monthly_expense'], 0, ',', '.') }} đ</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">⚡</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Tiền điện tháng') }}</p>
                </div>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($totals['monthly_energy_cost'], 0, ',', '.') }} đ</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ number_format($totals['monthly_energy_kwh'], 1, ',', '.') }} kWh</p>
            </div>
        </div>

        @php
            $netIncome = $totals['monthly_income'] - $totals['monthly_expense'];
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('Chênh lệch thu - chi tháng này') }}</span>
                <span class="text-xl font-bold {{ $netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $netIncome >= 0 ? '+' : '' }}{{ number_format($netIncome, 0, ',', '.') }} đ
                </span>
            </div>
        </div>

        {{-- Danh sách các nhà --}}
        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('Chi tiết từng nhà') }}</h3>

        <div class="space-y-4">
            @foreach($homeDetails as $detail)
                <a href="{{ route('dashboard', ['home_id' => $detail['home']->id]) }}"
                   class="block bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100">{{ $detail['home']->name }}</h4>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                                    {{ $detail['role'] === 'owner' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' :
                                       ($detail['role'] === 'manager' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400') }}">
                                    {{ __($detail['role']) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $detail['device_count'] }} thiết bị
                                @if($detail['home']->address)
                                    · {{ $detail['home']->address }}
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-4 sm:gap-6 text-sm text-right">
                            <div>
                                <p class="text-slate-400 dark:text-slate-500 text-xs">{{ __('Số dư') }}</p>
                                <p class="font-bold text-slate-900 dark:text-slate-100">{{ number_format($detail['balance'], 0, ',', '.') }} đ</p>
                            </div>
                            <div>
                                <p class="text-slate-400 dark:text-slate-500 text-xs">{{ __('Chi tiêu') }}</p>
                                <p class="font-bold text-red-600 dark:text-red-400">{{ number_format($detail['expense'], 0, ',', '.') }} đ</p>
                            </div>
                            <div>
                                <p class="text-slate-400 dark:text-slate-500 text-xs">{{ __('Điện') }}</p>
                                <p class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($detail['energy_kwh'], 1, ',', '.') }} kWh</p>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if($homeDetails->isEmpty())
            <div class="text-center py-12">
                <p class="text-4xl mb-3">🏠</p>
                <p class="text-slate-500 dark:text-slate-400">{{ __('Bạn chưa tham gia nhà nào.') }}</p>
                <a href="{{ route('homes.create') }}" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                    {{ __('Tạo nhà mới') }}
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
