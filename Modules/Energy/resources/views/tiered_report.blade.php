<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Phân tích tiền điện Bậc thang</h2>
            <div class="flex gap-3">
                <a href="{{ route('energy.index') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">Thống kê đo điện</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Bar -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                <form method="GET" action="{{ route('energy.tiered') }}" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label for="home_id" class="text-xs font-bold text-slate-500 uppercase">Ngôi nhà</label>
                        <select name="home_id" id="home_id" onchange="this.form.submit()" class="mt-1 block w-full bg-white border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                            @foreach($homes as $h)
                                <option value="{{ $h->id }}" @selected($h->id == $selectedHomeId)>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-64">
                        <label for="month" class="text-xs font-bold text-slate-500 uppercase">Tháng phân tích</label>
                        <input type="month" name="month" id="month" value="{{ $selectedMonth }}" onchange="this.form.submit()" class="mt-1 block w-full bg-white border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" />
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total kWh -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-50 rounded-xl text-amber-600 text-xl font-bold">⚡</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng điện tiêu thụ</h4>
                            <p class="text-2xl font-extrabold text-slate-800 mt-1">
                                {{ number_format($totalKwh, 1, ',', '.') }} kWh
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Total Cost -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 text-xl font-bold">💰</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hóa đơn dự tính (chưa VAT)</h4>
                            <p class="text-2xl font-extrabold text-slate-800 mt-1">
                                {{ number_format($totalCost, 0, ',', '.') }} đ
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Average Rate -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-50 rounded-xl text-blue-600 text-xl font-bold">📊</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Đơn giá trung bình thực tế</h4>
                            <p class="text-2xl font-extrabold text-slate-800 mt-1">
                                {{ $totalKwh > 0 ? number_format(round($totalCost / $totalKwh, 1), 0, ',', '.') : '0' }} đ/kWh
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tiers & Breakdown Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- EVN Tier Breakdown -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 font-outfit">Biểu lũy tiến bậc thang (EVN)</h3>
                        <span class="text-xs px-2.5 py-1 bg-slate-100 text-slate-500 rounded-lg font-semibold">
                            Biểu giá: {{ $tariffPlan?->name ?? 'Mặc định' }}
                        </span>
                    </div>

                    <div class="p-6 space-y-5">
                        @foreach($calculation as $tier)
                            @php
                                $tierLimit = $tier['limit_to'];
                                $limitStr = is_null($tierLimit) 
                                    ? 'Từ ' . $tier['limit_from'] . ' kWh trở lên' 
                                    : 'Từ ' . $tier['limit_from'] . ' - ' . $tierLimit . ' kWh';
                                
                                $tierCap = is_null($tierLimit) ? null : ($tierLimit - $tier['limit_from']);
                                $pctFilled = $tierCap > 0 ? min(100, round(($tier['consumed'] / $tierCap) * 100)) : ($tier['consumed'] > 0 ? 100 : 0);
                                
                                $barColor = $pctFilled === 100 && !is_null($tierLimit) 
                                    ? 'bg-indigo-600' 
                                    : ($pctFilled > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-200');
                            @endphp
                            <div class="space-y-1.5 p-3 rounded-xl border border-slate-100 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-850">Bậc {{ $tier['tier_number'] }}</h4>
                                        <p class="text-[11px] text-slate-450">{{ $limitStr }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold text-slate-500">{{ number_format($tier['rate'], 0, ',', '.') }}đ/kWh</span>
                                        <p class="text-sm font-extrabold text-slate-800 mt-0.5">+{{ number_format($tier['cost'], 0, ',', '.') }} đ</p>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <div class="flex justify-between text-[10px] text-slate-450 mb-1">
                                        <span>Đã sử dụng: <strong>{{ number_format($tier['consumed'], 1, ',', '.') }} kWh</strong></span>
                                        @if($tierCap > 0)
                                            <span>Hạn mức bậc: {{ $tierCap }} kWh</span>
                                        @endif
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $pctFilled }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Device Breakdown -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40">
                            <h3 class="font-bold text-slate-800 font-outfit">Đóng góp tiêu thụ của Thiết bị</h3>
                        </div>

                        <div class="p-6">
                            @if(!empty($deviceBreakdown))
                                <div class="space-y-4">
                                    @foreach($deviceBreakdown as $dev)
                                        <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/20 hover:bg-slate-50/80 transition duration-150">
                                            <div class="flex justify-between items-center mb-1.5">
                                                <div>
                                                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                                        <span>🔌</span>
                                                        {{ $dev['device']->name }}
                                                    </h4>
                                                    <p class="text-[10px] text-slate-450">{{ $dev['device']->room?->name }} • {{ $dev['device']->room?->home?->name }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-xs font-bold text-slate-700">{{ number_format($dev['kwh'], 1, ',', '.') }} kWh</span>
                                                    <p class="text-xs font-extrabold text-amber-600">~{{ number_format($dev['estimated_cost'], 0, ',', '.') }} đ</p>
                                                </div>
                                            </div>

                                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $dev['percentage'] }}%"></div>
                                            </div>
                                            <div class="flex justify-between text-[9px] text-slate-400 mt-1">
                                                <span>Tỷ lệ đóng góp hóa đơn:</span>
                                                <span class="font-bold">{{ $dev['percentage'] }}%</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-16 text-slate-400">
                                    <div class="text-5xl mb-4">🔋</div>
                                    <p class="text-sm font-semibold">Chưa có chỉ số đo điện năng nào trong tháng này</p>
                                    <p class="text-xs mt-1">Vui lòng nhập tay chỉ số đo điện hoặc liên kết smart plug để phân tích thiết bị.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
