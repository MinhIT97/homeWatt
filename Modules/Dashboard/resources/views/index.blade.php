<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                @php
                    $hour = now()->hour;
                    $greeting = 'Chào buổi sáng';
                    if ($hour >= 12 && $hour < 18) {
                        $greeting = 'Chào buổi chiều';
                    } elseif ($hour >= 18 || $hour < 4) {
                        $greeting = 'Chào buổi tối';
                    }
                @endphp
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight font-outfit">
                    {{ $greeting }}, {{ Auth::user()->name }}!
                </h2>
                <p class="text-xs text-slate-500 mt-1">Tổng quan mức tiêu thụ điện của gia đình bạn.</p>
            </div>

            <div class="flex items-center gap-3">
                @if($homes->isNotEmpty())
                    <form method="GET" class="flex items-center gap-2">
                        <label for="home_select" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ngôi nhà:</label>
                        <select id="home_select" name="home_id" onchange="this.form.submit()" class="bg-white border-slate-200 rounded-xl shadow-sm text-xs focus:border-blue-500 focus:ring-blue-500/20 pl-3 pr-8 py-2 font-bold text-slate-700 transition">
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div class="flex items-center gap-2">
                    @if($selectedHomeId)
                        <a href="{{ route('dashboard.export', ['home_id' => $selectedHomeId]) }}" class="flex items-center gap-1 px-3 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-xs font-bold text-slate-600 hover:text-blue-600 hover:border-blue-300 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            PDF
                        </a>
                    @endif
                    <div class="flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-xs font-bold text-slate-700 select-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($homes->isEmpty())
                <div class="glass-panel rounded-3xl p-12 text-center max-w-xl mx-auto border border-slate-200/60 shadow-xl relative overflow-hidden bg-white">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl"></div>
                    <div class="w-16 h-16 bg-blue-600/10 rounded-2xl flex items-center justify-center text-3xl text-blue-500 mx-auto mb-6 shadow-md shadow-blue-600/5">⚡</div>
                    <h3 class="text-xl font-extrabold text-slate-800 mb-2 font-outfit">Chào mừng đến với HomeWatt!</h3>
                    <p class="text-slate-500 text-sm mb-6 leading-relaxed">Hãy thêm ngôi nhà đầu tiên của bạn để bắt đầu theo dõi tiêu thụ điện.</p>
                    <a href="{{ route('homes.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-xl hover:from-blue-500 hover:to-cyan-400 text-sm font-bold shadow-lg shadow-blue-600/15 transition hover:-translate-y-0.5">
                        Thêm Ngôi Nhà Đầu Tiên
                    </a>
                </div>
            @else
                @php
                    $hasData = $stats['estimated_monthly_kwh'] > 0;
                    $todayKwh = $stats['today_kwh'];
                    $monthlyKwh = $stats['estimated_monthly_kwh'];
                    $monthlyCost = $stats['estimated_monthly_cost'];
                    $totalDev = $stats['total_devices'];
                    $activeDev = $selectedHomeId 
                        ? \Modules\Device\Models\Device::whereHas('room', fn($q) => $q->where('home_id', $selectedHomeId))->where('status', 'active')->count() 
                        : 0;
                    $pctYesterday = $stats['pct_vs_yesterday'];
                    $pctLastMonth = $stats['pct_vs_last_month'];

                    $recommendationDevice = null;
                    if ($selectedHomeId) {
                        $recommendationDevice = \Modules\Device\Models\Device::whereHas('room', fn($q) => $q->where('home_id', $selectedHomeId))
                            ->where(function($q) {
                                $q->where('name', 'like', '%lạnh%')
                                  ->orWhere('name', 'like', '%điều hòa%')
                                  ->orWhere('name', 'like', '%giặt%')
                                  ->orWhere('name', 'like', '%nóng%')
                                  ->orWhere('name', 'like', '%bếp%');
                            })
                            ->with('specification')
                            ->first();

                        if (!$recommendationDevice) {
                            $recommendationDevice = \Modules\Device\Models\Device::whereHas('room', fn($q) => $q->where('home_id', $selectedHomeId))
                                ->with('specification')
                                ->first();
                        }
                    }
                @endphp

                <div x-data="{
                    activeDevices: {{ $activeDev }},
                    totalDevices: {{ $totalDev }},
                    budgetLimit: parseFloat(localStorage.getItem('budgetLimit')) || 700000,
                    showLimitModal: false,
                    newLimitInput: '',
                    estimatedCost: {{ $monthlyCost }},
                    isScheduled: false,
                    saveBudget() {
                        let val = parseFloat(this.newLimitInput);
                        if (!isNaN(val) && val > 0) {
                            this.budgetLimit = val;
                            localStorage.setItem('budgetLimit', val);
                        }
                        this.showLimitModal = false;
                    }
                }" x-init="newLimitInput = budgetLimit" class="relative">

                    <!-- Budget Limit Setting Modal -->
                    <div x-show="showLimitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak style="display: none;">
                        <div @click.away="showLimitModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-sm w-full p-6 space-y-4">
                            <h4 class="font-extrabold text-slate-800 font-outfit text-base">Cài đặt hạn mức chi tiêu</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Đặt hạn mức tiền điện hàng tháng mong muốn. Hệ thống AI sẽ cảnh báo khi chi phí vượt hạn mức này.</p>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Số tiền hạn mức (₫)</label>
                                <input type="number" x-model="newLimitInput" class="w-full bg-slate-50 border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-850 transition" placeholder="Ví dụ: 700000">
                            </div>
                            <div class="flex gap-3 justify-end pt-2">
                                <button @click="showLimitModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 transition">Hủy</button>
                                <button @click="saveBudget()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/10 transition">Lưu hạn mức</button>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                        <!-- Card 1: Today -->
                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:scale-[1.02] hover:shadow-md transition duration-200 flex flex-col justify-between h-32 relative overflow-hidden">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Điện năng tiêu thụ hôm nay</p>
                                <h4 class="text-2xl font-extrabold text-slate-900 font-outfit mt-1.5">{{ number_format($todayKwh, 2) }} <span class="text-sm font-medium text-slate-400">kWh</span></h4>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                @if($pctYesterday !== null)
                                    <span class="text-xs font-bold flex items-center gap-0.5 {{ $pctYesterday <= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $pctYesterday <= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}"></path>
                                        </svg>
                                        {{ abs($pctYesterday) }}% so với hôm qua
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">—</span>
                                @endif
                                <svg class="w-16 h-8 text-blue-500" viewBox="0 0 100 30" fill="none">
                                    <path d="M0 25 C10 20, 20 22, 30 15 C40 8, 50 18, 60 12 C70 5, 80 14, 90 8 C95 5, 100 2, 100 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Card 2: Monthly -->
                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:scale-[1.02] hover:shadow-md transition duration-200 flex flex-col justify-between h-32 relative overflow-hidden">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Điện năng tháng này</p>
                                <h4 class="text-2xl font-extrabold text-slate-900 font-outfit mt-1.5">{{ number_format($monthlyKwh, 1) }} <span class="text-sm font-medium text-slate-400">kWh</span></h4>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                @if($pctLastMonth !== null)
                                    <span class="text-xs font-bold flex items-center gap-0.5 {{ $pctLastMonth <= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $pctLastMonth <= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}"></path>
                                        </svg>
                                        {{ abs($pctLastMonth) }}% so với tháng trước
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">—</span>
                                @endif
                                <div class="flex items-end gap-1 h-8">
                                    @foreach(array_slice($dailyData, -5) ?: [0,0,0,0,0] as $val)
                                        @php $maxBar = max($dailyData ?: [1]); $h = $maxBar > 0 ? max(3, round(($val / $maxBar) * 32)) : 3; @endphp
                                        <span class="w-1.5 rounded-sm {{ $val > 0 ? 'bg-emerald-400' : 'bg-slate-200' }}" style="height: {{ $h }}px"></span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Cost -->
                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:scale-[1.02] hover:shadow-md transition duration-200 flex flex-col justify-between h-32 relative overflow-hidden">
                            <div>
                                <div class="flex justify-between items-start">
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ước tính tiền điện</p>
                                    <button @click="showLimitModal = true" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600 transition" title="Đặt hạn mức">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <h4 class="text-2xl font-extrabold text-slate-900 font-outfit">{{ number_format($monthlyCost) }} <span class="text-xs font-bold text-slate-500 font-sans">đ</span></h4>
                                    <span x-show="estimatedCost > budgetLimit" class="ml-2 px-1.5 py-0.5 rounded bg-red-50 text-[9px] font-extrabold text-red-500 border border-red-100 uppercase tracking-wider animate-pulse">
                                        ⚠️ Vượt hạn mức
                                    </span>
                                </div>
                            </div>
                            <div class="w-full">
                                <div class="flex justify-between text-[9px] font-bold text-slate-400 mb-1">
                                    <span>HẠN MỨC: <span x-text="Math.round(budgetLimit).toLocaleString()"></span>đ</span>
                                    <span x-text="Math.round((estimatedCost / budgetLimit) * 100) + '%'"></span>
                                </div>
                                <div class="bg-slate-100 rounded-full h-1 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" 
                                         :class="estimatedCost > budgetLimit ? 'bg-red-500' : (estimatedCost > budgetLimit * 0.8 ? 'bg-amber-500' : 'bg-blue-500')"
                                         :style="'width: ' + Math.min((estimatedCost / budgetLimit) * 100, 100) + '%'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Devices -->
                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:scale-[1.02] hover:shadow-md transition duration-200 flex flex-col justify-between h-32 relative overflow-hidden">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Thiết bị đang hoạt động</p>
                                <h4 class="text-2xl font-extrabold text-slate-900 font-outfit mt-1.5" x-text="activeDevices"></h4>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs font-semibold text-slate-400">
                                    trong tổng số <span x-text="totalDevices"></span> thiết bị
                                </span>
                                <svg class="w-8 h-8 transform -rotate-90">
                                    <circle cx="16" cy="16" r="12" stroke="#E2E8F0" stroke-width="3.5" fill="transparent" />
                                    <circle cx="16" cy="16" r="12" stroke="#8B5CF6" stroke-width="3.5" fill="transparent"
                                        stroke-dasharray="75.39" :stroke-dashoffset="75.39 * (1 - min(1, activeDevices / max(totalDevices, 1)))" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Consumption Line Chart -->
                        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 flex flex-col justify-between">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-extrabold text-slate-800 font-outfit text-base">Biểu đồ tiêu thụ điện</h3>
                                <span class="bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 px-3 py-1.5 font-outfit">7 ngày qua</span>
                            </div>
                            <div class="w-full">
                                <canvas id="consumptionChart" class="h-64"></canvas>
                            </div>
                        </div>

                        <!-- Top Consumer Devices -->
                        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="font-extrabold text-slate-800 font-outfit text-base">Thiết bị tiêu thụ nhiều nhất</h3>
                                    <a href="{{ route('devices.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Xem tất cả</a>
                                </div>

                                <div class="space-y-5">
                                    @if($topDevices->isNotEmpty())
                                        @php
                                            $maxKwh = $topDevices->max('total_kwh') ?: 1;
                                            $icons = ['air' => '❄️', 'conditioner' => '❄️', 'lạnh' => '❄️', 'ac' => '❄️', 'fridge' => '🥬', 'tủ lạnh' => '🥬', 'freezer' => '🥬', 'tv' => '📺', 'tivi' => '📺', 'television' => '📺', 'washer' => '🧺', 'giặt' => '🧺', 'dryer' => '🧺', 'heater' => '🔥', 'nước nóng' => '🔥', 'bình nóng' => '🔥'];
                                        @endphp
                                        @foreach($topDevices as $summary)
                                            @php
                                                $name = $summary->device?->name ?? 'Thiết bị';
                                                $lowerName = strtolower($name);
                                                $icon = '🔌';
                                                foreach($icons as $key => $emoji) {
                                                    if (str_contains($lowerName, $key)) { $icon = $emoji; break; }
                                                }
                                            @endphp
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-lg shrink-0 border border-slate-100 shadow-sm">{{ $icon }}</div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex justify-between text-xs font-bold text-slate-800 mb-1.5">
                                                        <span class="truncate pr-2">{{ $name }}</span>
                                                        <span class="shrink-0 text-slate-500 font-mono">{{ number_format($summary->total_kwh, 1) }} kWh</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div class="flex-grow bg-slate-100 rounded-full h-2 overflow-hidden">
                                                            <div class="bg-blue-500 h-full rounded-full transition-all duration-500" style="width: {{ ($summary->total_kwh / $maxKwh) * 100 }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="py-12 text-center flex flex-col items-center justify-center h-full">
                                            <span class="text-3xl mb-2">📊</span>
                                            <p class="text-slate-400 text-sm font-bold">Chưa có dữ liệu tiêu thụ</p>
                                            <p class="text-slate-400 text-xs mt-1">Ghi nhận số đo thiết bị để xem xếp hạng.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Recently Added & Room Distribution & Eco Score -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Panel (2/3): Devices & AI Scheduler -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Recently Added Devices -->
                            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                                <h3 class="font-extrabold text-slate-800 font-outfit text-base mb-5">Thiết bị mới thêm</h3>

                                @php
                                    $recentDevices = $selectedHomeId
                                        ? \Modules\Device\Models\Device::whereHas('room', fn($q) => $q->where('home_id', $selectedHomeId))->latest()->take(3)->get()
                                        : collect();
                                    $devIcons = ['air' => '❄️', 'conditioner' => '❄️', 'lạnh' => '❄️', 'ac' => '❄️', 'fridge' => '🥬', 'tủ lạnh' => '🥬', 'freezer' => '🥬', 'tv' => '📺', 'tivi' => '📺', 'television' => '📺', 'washer' => '🧺', 'giặt' => '🧺', 'dryer' => '🧺', 'heater' => '🔥', 'nước nóng' => '🔥', 'bình nóng' => '🔥'];
                                @endphp

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    @foreach($recentDevices as $device)
                                        @php
                                            $devLower = strtolower($device->name);
                                            $emoji = '🔌';
                                            foreach($devIcons as $key => $emo) { if (str_contains($devLower, $key)) { $emoji = $emo; break; } }
                                        @endphp
                                        <div x-data="{ isToggled: {{ $device->status === 'active' ? 'true' : 'false' }} }" 
                                             x-init="$watch('isToggled', val => val ? activeDevices++ : activeDevices--)"
                                             :class="isToggled ? 'border-slate-200 bg-white shadow-sm' : 'border-slate-150 bg-slate-50/50 opacity-70'"
                                             class="border rounded-2xl p-4 flex flex-col justify-between h-40 hover:border-blue-200 hover:shadow-md hover:shadow-blue-500/5 hover:-translate-y-0.5 transition duration-250">
                                            <div class="flex justify-between items-start">
                                                <div class="w-9 h-9 rounded-xl bg-blue-50/80 border border-blue-100/50 flex items-center justify-center text-lg shadow-sm">{{ $emoji }}</div>
                                                
                                                <!-- Switch Toggle Button -->
                                                <button @click="isToggled = !isToggled" 
                                                        class="w-8 h-4.5 rounded-full p-0.5 transition-colors duration-200 focus:outline-none"
                                                        :class="isToggled ? 'bg-blue-500' : 'bg-slate-200'">
                                                    <div class="w-3.5 h-3.5 bg-white rounded-full shadow transform duration-200"
                                                         :class="isToggled ? 'translate-x-3.5' : 'translate-x-0'"></div>
                                                </button>
                                            </div>
                                            <div class="space-y-1">
                                                <h5 class="text-sm font-bold text-slate-800 truncate tracking-tight">{{ $device->name }}</h5>
                                                <p class="text-xs text-slate-400 font-medium leading-none">{{ $device->room?->name }}</p>
                                                <p class="text-xs font-bold text-slate-500 font-mono pt-0.5">{{ $device->specification?->rated_power ? number_format($device->specification->rated_power).' W' : '—' }}</p>
                                            </div>
                                        </div>
                                    @endforeach



                                    <a href="{{ route('devices.create') }}" class="border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center gap-2 text-slate-400 hover:text-blue-500 hover:border-blue-400 hover:bg-blue-50/5 transition cursor-pointer h-40">
                                        <span class="w-9 h-9 bg-slate-50 rounded-full flex items-center justify-center border border-slate-150 shadow-sm text-base font-bold">+</span>
                                        <span class="text-xs font-bold font-outfit">Thêm thiết bị</span>
                                    </a>
                                </div>
                            </div>

                            <!-- AI Off-Peak Scheduler Banner -->
                            <div class="bg-gradient-to-r from-blue-50/40 via-cyan-50/20 to-transparent border border-blue-100/70 rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 relative overflow-hidden shadow-sm">
                                <div class="absolute -right-8 -top-8 w-24 h-24 bg-blue-100/20 rounded-full blur-xl pointer-events-none"></div>
                                <div class="flex gap-3 min-w-0">
                                    <span class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-lg text-blue-600 shrink-0 shadow-inner">💡</span>
                                    <div>
                                        <h5 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider font-outfit">Đề xuất AI tối ưu</h5>
                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                            @if($recommendationDevice)
                                                Chuyển hoạt động của <strong class="text-slate-700">{{ $recommendationDevice->name }}</strong> ({{ $recommendationDevice->specification?->rated_power ? number_format($recommendationDevice->specification->rated_power).'W' : 'công suất lớn' }}) từ 18:00 (giờ cao điểm) sang sau 22:00 (giờ thấp điểm) để tiết kiệm ước tính <strong class="text-blue-600">12,000₫/ngày</strong>.
                                            @else
                                                Thêm thiết bị công suất lớn (máy giặt, điều hòa, bình nóng lạnh) để nhận đề xuất tối ưu hóa điện năng từ AI.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if($recommendationDevice)
                                    <button @click="isScheduled = !isScheduled" 
                                            class="shrink-0 w-full sm:w-auto px-4.5 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm"
                                            :class="isScheduled ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-blue-600 text-white hover:bg-blue-500'">
                                        <span x-show="isScheduled">
                                            <svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </span>
                                        <span x-text="isScheduled ? 'Đã lên lịch tự động' : 'Lên lịch tự động'"></span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Right Column (1/3): Room Chart & Eco Score -->
                        <div class="space-y-6">
                            <!-- Room Allocation Donut -->
                            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 flex flex-col justify-between">
                                <h3 class="font-extrabold text-slate-800 font-outfit text-base mb-5">Phân bố theo phòng</h3>

                                @php
                                    $roomsData = collect();
                                    $hasRealRoomsData = false;
                                    if ($selectedHomeId) {
                                        $home = \Modules\Home\Models\Home::with('rooms')->find($selectedHomeId);
                                        if ($home) {
                                            foreach ($home->rooms as $room) {
                                                $roomKwh = \Modules\Energy\Models\MonthlyEnergySummary::where('home_id', $selectedHomeId)
                                                    ->whereHas('device', fn($q) => $q->where('room_id', $room->id))
                                                    ->where('year', now()->year)
                                                    ->where('month', now()->month)
                                                    ->sum('total_kwh');
                                                $roomsData->push(['name' => $room->name, 'kwh' => $roomKwh]);
                                            }
                                        }
                                    }
                                    $roomsData = $roomsData->sortByDesc('kwh')->values();
                                    $totalRoomKwh = $roomsData->sum('kwh') ?: 0;
                                    
                                    if ($roomsData->isNotEmpty() && $totalRoomKwh > 0) {
                                        $hasRealRoomsData = true;
                                    }
                                    $chartColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#64748B'];

                                    $ecoScore = $totalRoomKwh == 0 ? 100 : max(40, round(100 - ($totalRoomKwh / 10)));
                                    $co2Saved = round($totalRoomKwh * 0.1 * 0.52, 1);
                                    $trees = max(0, round($co2Saved / 4));
                                    $strokeOffset = 276.46 * (1 - $ecoScore / 100);
                                    
                                    if ($ecoScore >= 80) {
                                        $badgeTitle = 'Chiến binh xanh';
                                        $badgeColorClass = 'text-emerald-600';
                                    } elseif ($ecoScore >= 60) {
                                        $badgeTitle = 'Người bảo vệ';
                                        $badgeColorClass = 'text-blue-600';
                                    } else {
                                        $badgeTitle = 'Mới bắt đầu';
                                        $badgeColorClass = 'text-slate-600';
                                    }
                                @endphp

                                @if($hasRealRoomsData)
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-36 h-36 shrink-0">
                                            <canvas id="roomAllocationChart"></canvas>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                <span class="text-base font-extrabold text-slate-800 font-mono">{{ number_format($totalRoomKwh, 1) }}</span>
                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">kWh</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow space-y-2 text-xs font-semibold text-slate-700">
                                            @foreach($roomsData->take(5) as $idx => $room)
                                                @php
                                                    $color = $chartColors[$idx] ?? '#64748B';
                                                    $pct = round(($room['kwh'] / $totalRoomKwh) * 100);
                                                @endphp
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-1.5 min-w-0 pr-1">
                                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $color }}"></span>
                                                        <span class="truncate text-slate-600 font-medium">{{ $room['name'] }}</span>
                                                    </div>
                                                    <span class="shrink-0 text-slate-800 font-bold text-right font-mono">{{ $pct }}%</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="py-8 text-center flex flex-col items-center justify-center h-full w-full">
                                        <span class="text-3xl mb-2">🏘️</span>
                                        <p class="text-slate-400 text-sm font-bold animate-pulse">Chưa có dữ liệu phân bố</p>
                                        <p class="text-slate-400 text-xs mt-1">Ghi nhận số đo thiết bị để xem phân bổ theo phòng.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Eco-Score Card -->
                            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 flex flex-col justify-between relative overflow-hidden">
                                <h3 class="font-extrabold text-slate-800 font-outfit text-base mb-5">Chỉ số tiết kiệm xanh</h3>
                                
                                <div class="flex items-center gap-4">
                                    <!-- Gauge circular chart -->
                                    <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                                        <svg class="w-full h-full transform -rotate-90">
                                            <circle cx="56" cy="56" r="44" stroke="#F1F5F9" stroke-width="8" fill="transparent" />
                                            <circle cx="56" cy="56" r="44" stroke="#10B981" stroke-width="8" fill="transparent" 
                                                stroke-dasharray="276.46" stroke-dashoffset="{{ $strokeOffset }}" stroke-linecap="round" />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-2xl font-extrabold text-slate-800 font-outfit">{{ $ecoScore }}</span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Điểm</span>
                                        </div>
                                    </div>

                                    <!-- Details -->
                                    <div class="flex-grow space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">🌳</span>
                                            <div class="min-w-0">
                                                <p class="text-[10px] text-slate-400 font-semibold leading-none">Giảm thiểu CO2</p>
                                                <p class="text-xs font-bold text-slate-800 mt-1 truncate">{{ number_format($co2Saved, 1) }} kg CO2</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">🌱</span>
                                            <div class="min-w-0">
                                                <p class="text-[10px] text-slate-400 font-semibold leading-none">Cây tương đương</p>
                                                <p class="text-xs font-bold text-slate-800 mt-1 truncate">{{ $trees }} cây xanh</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">🏆</span>
                                            <div class="min-w-0">
                                                <p class="text-[10px] text-slate-400 font-semibold leading-none">Danh hiệu</p>
                                                <p class="text-xs font-bold {{ $badgeColorClass }} mt-1 truncate">{{ $badgeTitle }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed mt-4 pt-4 border-t border-slate-100">
                                    Hành vi sử dụng điện của bạn đang tốt hơn {{ $ecoScore }}% số hộ gia đình xung quanh. Tiếp tục phát huy!
                                </p>
                            </div>
                        </div>
                    <!-- Saving Suggestions -->
                    @if(!empty($suggestions))
                        <div class="mt-6">
                            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200/60 shadow-sm p-6">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="text-xl">💡</span>
                                    <h3 class="font-extrabold text-slate-800 font-outfit text-base">Gợi ý tiết kiệm điện</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($suggestions as $s)
                                        <div class="bg-white rounded-xl border {{ $s['priority'] === 'high' ? 'border-emerald-300' : 'border-slate-200' }} p-4 flex items-start gap-3">
                                            <span class="text-2xl shrink-0">{{ $s['icon'] }}</span>
                                            <div class="min-w-0">
                                                <h5 class="text-sm font-bold text-slate-800">{{ $s['title'] }}</h5>
                                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $s['detail'] }}</p>
                                                <p class="text-xs font-bold text-emerald-600 mt-2">~{{ number_format($s['saving_cost']) }}đ/tháng</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div> <!-- Closes x-data -->
            @endif
        </div> <!-- Closes max-w-7xl... -->
    </div> <!-- Closes py-6 -->

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const labels = @json($dailyLabels);
            const data = @json($dailyData);
            const lastMonthData = @json($lastMonthDailyData);

            // Line Chart
            const ctxLine = document.getElementById('consumptionChart');
            if (ctxLine && data.length > 0) {
                const lineCtx = ctxLine.getContext('2d');
                const gradient = lineCtx.createLinearGradient(0, 0, 0, ctxLine.offsetHeight || 250);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.22)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0.00)');

                const gradient2 = lineCtx.createLinearGradient(0, 0, 0, ctxLine.offsetHeight || 250);
                gradient2.addColorStop(0, 'rgba(148, 163, 184, 0.12)');
                gradient2.addColorStop(1, 'rgba(148, 163, 184, 0.00)');

                const allVals = [...data, ...lastMonthData.filter(v => v > 0)];
                const maxVal = Math.max(...allVals, 1);
                const yMax = Math.ceil(maxVal * 1.5);

                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Tháng này',
                            data: data,
                            borderColor: '#3B82F6',
                            borderWidth: 3,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#FFFFFF',
                            pointBorderColor: '#3B82F6',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#3B82F6',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 2
                        @if(!empty(array_filter($lastMonthDailyData)))
                        }, {
                            label: 'Tháng trước',
                            data: lastMonthData,
                            borderColor: '#94A3B8',
                            borderWidth: 2,
                            borderDash: [5, 3],
                            backgroundColor: gradient2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#FFFFFF',
                            pointBorderColor: '#94A3B8',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#94A3B8',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 2
                        @endif
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 10, weight: 'bold' }, color: '#64748B' } },
                            tooltip: {
                                backgroundColor: '#1E293B',
                                titleFont: { size: 11, weight: 'bold' },
                                bodyFont: { size: 12, weight: 'bold' },
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toFixed(2) + ' kWh';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: 'bold' }, color: '#94A3B8' }
                            },
                            y: {
                                min: 0,
                                max: yMax,
                                ticks: {
                                    stepSize: Math.max(1, Math.round(yMax / 4)),
                                    font: { size: 10, weight: 'bold' },
                                    color: '#94A3B8'
                                },
                                grid: { color: '#F1F5F9', drawBorder: false }
                            }
                        }
                    }
                });
            }

            // Donut Chart
            const ctxDonut = document.getElementById('roomAllocationChart');
            if (ctxDonut) {
                const names = @json($roomsData->pluck('name'));
                const values = @json($roomsData->pluck('kwh'));

                new Chart(ctxDonut, {
                    type: 'doughnut',
                    data: {
                        labels: names,
                        datasets: [{
                            data: values,
                            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#64748B'],
                            borderWidth: 2,
                            borderColor: '#FFFFFF',
                            hoverOffset: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1E293B',
                                bodyFont: { size: 11, weight: 'bold' },
                                padding: 8,
                                cornerRadius: 8,
                                displayColors: false
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
