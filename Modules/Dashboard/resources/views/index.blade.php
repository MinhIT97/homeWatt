<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight font-outfit">Bảng Điều Khiển</h2>
                <p class="text-xs text-slate-500 mt-1">Giám sát và phân tích lượng điện tiêu thụ của gia đình bạn</p>
            </div>
            @if($homes->isNotEmpty())
                <form method="GET" class="flex items-center gap-2">
                    <label for="home_select" class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ngôi nhà:</label>
                    <select id="home_select" name="home_id" onchange="this.form.submit()" class="bg-white/80 backdrop-blur-sm border-slate-200 rounded-xl shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 pl-3 pr-8 py-2 font-medium text-slate-700 transition">
                        @foreach($homes as $home)
                            <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>🏠 {{ $home->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($homes->isEmpty())
                <div class="glass-panel rounded-3xl p-12 text-center max-w-xl mx-auto border border-slate-200/60 shadow-xl relative overflow-hidden">
                    <!-- Background ambient -->
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary-500/10 rounded-full blur-2xl"></div>
                    
                    <div class="w-16 h-16 bg-primary-600/10 rounded-2xl flex items-center justify-center text-3xl text-primary-500 mx-auto mb-6 shadow-md shadow-primary-600/5">⚡</div>
                    <h3 class="text-xl font-extrabold text-slate-800 mb-2 font-outfit">Chào mừng đến với HomeWatt!</h3>
                    <p class="text-slate-500 text-sm mb-6 leading-relaxed">Hãy thêm ngôi nhà đầu tiên của bạn để bắt đầu lập hồ sơ kỹ thuật và theo dõi hóa đơn tiêu thụ điện.</p>
                    <a href="{{ route('homes.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-750 text-white rounded-xl hover:from-primary-500 hover:to-primary-650 text-sm font-bold shadow-lg shadow-primary-600/15 transition hover:-translate-y-0.5">
                        Thêm Ngôi Nhà Đầu Tiên
                    </a>
                </div>
            @else
                <!-- Statistics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Devices Count -->
                    <div class="glass-panel rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-200">
                        <div class="p-3.5 bg-indigo-50 text-indigo-600 rounded-xl text-xl font-bold">🔌</div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Thiết Bị</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1 font-outfit">{{ $stats['total_devices'] }}</p>
                        </div>
                    </div>

                    <!-- Rooms Count -->
                    <div class="glass-panel rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-200">
                        <div class="p-3.5 bg-cyan-50 text-cyan-600 rounded-xl text-xl font-bold">🚪</div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Phòng</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1 font-outfit">{{ $stats['total_rooms'] }}</p>
                        </div>
                    </div>

                    <!-- Monthly kWh -->
                    <div class="glass-panel rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-200">
                        <div class="p-3.5 bg-yellow-50 text-yellow-600 rounded-xl text-xl font-bold">⚡</div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Điện Năng Ước Tính</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1 font-outfit">
                                {{ number_format($stats['estimated_monthly_kwh'], 1) }} <span class="text-xs font-medium text-slate-400">kWh/tháng</span>
                            </p>
                        </div>
                    </div>

                    <!-- Monthly Cost -->
                    <div class="glass-panel rounded-2xl p-5 border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-200">
                        <div class="p-3.5 bg-green-50 text-green-600 rounded-xl text-xl font-bold">💰</div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Chi Phí Ước Tính</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1 font-outfit">
                                {{ number_format($stats['estimated_monthly_cost']) }} <span class="text-xs font-medium text-slate-400">VND</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left/Main Column: Top Consumers -->
                    <div class="lg:col-span-2 space-y-8">
                        @if($topDevices->isNotEmpty())
                        <div class="glass-panel rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="font-extrabold text-slate-800 font-outfit">Thiết Bị Tiêu Thụ Nhiều Nhất</h3>
                                <span class="text-xs bg-slate-100 text-slate-600 font-semibold px-2.5 py-1 rounded-full uppercase">Hạng Hạng Tháng</span>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Thiết Bị</th>
                                            <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Sản Lượng (kWh)</th>
                                            <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Chi Phí Dự Tính</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white/40">
                                        @php
                                            $maxKwh = $topDevices->max('total_kwh') ?: 1;
                                        @endphp
                                        @foreach($topDevices as $summary)
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                                    <div class="flex flex-col">
                                                        <span>{{ $summary->device?->name ?? '—' }}</span>
                                                        <!-- visual mini-bar -->
                                                        <div class="w-28 bg-slate-100 rounded-full h-1 mt-1.5 overflow-hidden">
                                                            <div class="bg-primary-500 h-full rounded-full" style="width: {{ ($summary->total_kwh / $maxKwh) * 100 }}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-slate-800 text-right font-mono font-bold">{{ number_format($summary->total_kwh, 1) }}</td>
                                                <td class="px-6 py-4 text-sm text-primary-600 text-right font-bold">{{ number_format($summary->estimated_cost) }} VND</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @else
                        <div class="glass-panel rounded-2xl p-8 border border-slate-200/60 shadow-sm text-center">
                            <p class="text-slate-400 text-sm">Chưa có đủ số liệu đo đạc năng lượng của các thiết bị để xếp hạng.</p>
                        </div>
                        @endif
                    </div>

                    <!-- Right Column: Data Quality & Tips -->
                    <div class="space-y-6">
                        <!-- Data Quality Widget -->
                        <div class="glass-panel rounded-2xl p-6 border border-slate-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-extrabold text-slate-800 font-outfit">Chất Lượng Dữ Liệu</h3>
                                <span class="p-1 px-2 rounded-lg bg-green-50 text-[10px] font-bold text-green-600 uppercase">Tin Cậy</span>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span class="text-slate-400">Đã đo đạc thực tế:</span>
                                    <span class="text-green-500 font-bold">{{ round($stats['measured_ratio'] * 100) }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                    <div class="bg-gradient-to-r from-primary-500 to-green-400 h-full rounded-full shadow-inner" style="width: {{ $stats['measured_ratio'] * 100 }}%"></div>
                                </div>
                            </div>

                            <p class="text-xs text-slate-400 leading-relaxed">
                                Tỷ lệ đo lường cao hơn giúp cải thiện độ chính xác dự báo chi phí tiền điện. Thêm thông số ổ cắm thông minh hoặc công tơ để tăng chỉ số này.
                            </p>
                        </div>

                        <!-- Premium Quick Guide Card -->
                        <div class="bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-2xl p-6 border border-slate-800/80 shadow-md space-y-4 relative overflow-hidden">
                            <!-- Glow decoration -->
                            <div class="absolute -bottom-8 -right-8 w-24 h-24 bg-primary-500/20 rounded-full blur-xl pointer-events-none"></div>
                            
                            <h4 class="font-bold text-white text-sm uppercase tracking-wider">⚡ Mẹo tối ưu hóa</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                AI phát hiện thiết bị Điều hòa nhiệt độ tiêu thụ điện chiếm phần lớn hóa đơn gia đình. Hãy cân nhắc đặt nhiệt độ khuyến nghị 26°C và hẹn giờ tắt vào ban đêm để tiết kiệm từ 10-15% sản lượng điện năng tiêu dùng.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
