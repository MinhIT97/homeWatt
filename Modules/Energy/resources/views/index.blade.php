<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Đo Lường & Ước Tính Điện Năng</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Record Reading Form -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">Ghi nhận chỉ số đo</div>
                    <form method="POST" action="{{ route('energy.store') }}" class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="device_id" value="Chọn thiết bị" />
                                <select id="device_id" name="device_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-850 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                                    <option value="">Chọn thiết bị...</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->room->home->name }} / {{ $d->room->name }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="watts" value="Công suất (W)" />
                                    <x-text-input id="watts" name="watts" type="number" step="0.01" class="mt-1 block w-full" placeholder="Ví dụ: 150" />
                                </div>
                                <div>
                                    <x-input-label for="kwh" value="Sản lượng (kWh)" />
                                    <x-text-input id="kwh" name="kwh" type="number" step="0.001" class="mt-1 block w-full" placeholder="Ví dụ: 1.25" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="source" value="Nguồn dữ liệu" />
                                <select id="source" name="source" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                                    <option value="manual">Manual (Nhập tay)</option>
                                    <option value="measured">Measured (Đo thực tế)</option>
                                    <option value="ai">AI (AI dự đoán)</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="recorded_at" value="Thời gian ghi nhận" />
                                <x-text-input id="recorded_at" name="recorded_at" type="datetime-local" class="mt-1 block w-full" required value="{{ now()->format('Y-m-d\TH:i') }}" />
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-100 mt-6">
                            <x-primary-button class="w-full justify-center">Lưu số đo</x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Calculate Estimate Form -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">Ước tính tiêu thụ tháng</div>
                        <form method="POST" action="{{ route('energy.calculate') }}" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="calc_device_id" value="Chọn thiết bị" />
                                <select id="calc_device_id" name="device_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-850 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                                    <option value="">Chọn thiết bị...</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="year" value="Năm" />
                                    <x-text-input id="year" name="year" type="number" class="mt-1 block w-full" required value="{{ now()->year }}" />
                                </div>
                                <div>
                                    <x-input-label for="month" value="Tháng" />
                                    <x-text-input id="month" name="month" type="number" min="1" max="12" class="mt-1 block w-full" required value="{{ now()->month }}" />
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-6">
                                <x-primary-button class="w-full justify-center">Tính toán ước lượng</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Estimates -->
            <div class="mt-8 glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-850 font-outfit bg-slate-50/40">Các ước tính gần đây</div>
                @if($estimates->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Thiết bị</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Chu kỳ</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Sản lượng ước tính (kWh)</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Phương pháp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($estimates as $e)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 text-sm font-bold text-slate-850">{{ $e->device?->name }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-600 text-right">{{ $e->period_start?->format('Y-m') }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-accent-650 text-right">{{ number_format($e->estimated_kwh, 1) }} kWh</td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase border bg-primary-50 text-primary-600 border-primary-150">
                                                {{ $e->method }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-slate-500 text-sm text-center">Chưa có ước tính điện năng nào được ghi nhận.</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
