<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $device->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Device Info -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-outfit font-bold text-slate-850 text-base mb-4 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span>ℹ️</span> Thông tin thiết bị
                        </h3>
                        <dl class="space-y-3.5">
                            <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loại máy</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->deviceType?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Thương hiệu</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->brand ?? '—' }}</dd></div>
                            <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Model</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->model ?? '—' }}</dd></div>
                            <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Phòng</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->room->name }}</dd></div>
                            <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ngôi nhà</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->room->home->name }}</dd></div>
                            <div class="flex justify-between items-center">
                                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng thái</dt>
                                <dd>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ $device->status }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Power Specs -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-outfit font-bold text-slate-850 text-base mb-4 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span>⚡</span> Thông số công suất
                        </h3>
                        @if($device->specification)
                            <dl class="space-y-3.5">
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Công suất định mức</dt><dd class="text-sm font-bold text-accent-650">{{ $device->specification->rated_power }} W</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Công suất tối đa</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->max_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Công suất chờ</dt><dd class="text-sm font-semibold text-slate-850">{{ $device->specification->standby_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Điện áp</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->voltage ?? '—' }} V</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cường độ dòng điện</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->current ?? '—' }} A</dd></div>
                            </dl>
                        @else
                            <p class="text-sm text-slate-500 py-4 text-center">Chưa ghi nhận thông số.</p>
                        @endif
                    </div>
                </div>

                <!-- Usage Profile -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-outfit font-bold text-slate-850 text-base mb-4 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span>📅</span> Tần suất hoạt động
                        </h3>
                        @if($device->usageProfile)
                            <dl class="space-y-3.5">
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Số giờ/ngày</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->hours_per_day }} giờ</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Số ngày/tuần</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->days_per_week }} ngày</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hệ số tải</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->duty_cycle ? ($device->usageProfile->duty_cycle * 100).'%' : '—' }}</dd></div>
                                <div class="flex justify-between items-center"><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nguồn dữ liệu</dt><dd class="text-xs font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded border border-primary-200 capitalize">{{ $device->usageProfile->source }}</dd></div>
                            </dl>
                        @else
                            <p class="text-sm text-slate-500 py-4 text-center">Chưa có tần suất sử dụng.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
