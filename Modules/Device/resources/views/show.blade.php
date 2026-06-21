<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $device->name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ $device->room->home->name }} / {{ $device->room->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('devices.edit', $device) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">Chỉnh sửa</a>
                <a href="{{ route('energy.calculate') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white text-sm font-semibold rounded-xl shadow-md transition">Tính tiền điện</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Cột 1+2: Ảnh chụp tem nhãn + Upload + AI Results -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Upload & Gallery Section -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                        <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800 font-outfit flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Ảnh tem nhãn thiết bị
                            </h3>
                            <span class="text-xs text-slate-400 font-medium">{{ $device->media->count() }} ảnh</span>
                        </div>

                        <div class="p-6">
                            <!-- Upload form -->
                            <form method="POST" action="{{ route('devices.upload-image', $device) }}" enctype="multipart/form-data" class="mb-6">
                                @csrf
                                <div class="flex items-center gap-4">
                                    <label class="flex-1 flex items-center gap-3 px-5 py-4 border-2 border-dashed border-slate-300 hover:border-primary-400 rounded-xl cursor-pointer transition bg-slate-50/50 hover:bg-primary-50/30 group">
                                        <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center group-hover:scale-110 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-700 group-hover:text-primary-700">Chụp hoặc tải ảnh tem nhãn</p>
                                            <p class="text-xs text-slate-400 mt-0.5">JPEG, PNG, WebP — tối đa 20MB</p>
                                        </div>
                                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()" required />
                                    </label>
                                </div>
                            </form>

                            <!-- Image Gallery -->
                            @if($device->media->isNotEmpty())
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @foreach($device->media as $media)
                                        @php
                                            $mediaAnalysis = $analyses->firstWhere('media_id', $media->id);
                                        @endphp
                                        <div class="relative group rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-[4/3]">
                                            <img src="{{ route('media.serve', $media) }}" class="w-full h-full object-cover" alt="Device label" loading="lazy" />

                                            <!-- Overlay actions -->
                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition flex flex-col justify-end p-3 gap-2">
                                                <div class="flex gap-2">
                                                    @if(!$mediaAnalysis || $mediaAnalysis->status === 'failed')
                                                        <form method="POST" action="{{ route('ai.analyses.store') }}" class="flex-1">
                                                            @csrf
                                                            <input type="hidden" name="media_id" value="{{ $media->id }}" />
                                                            <button type="submit" class="w-full text-xs px-3 py-2 bg-accent-500 hover:bg-accent-400 text-white font-bold rounded-lg transition flex items-center justify-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                                AI Phân tích
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form method="POST" action="{{ route('devices.delete-image', [$device, $media]) }}" onsubmit="return confirm('Xóa ảnh này?')" class="flex-1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="w-full text-xs px-3 py-2 bg-red-500/80 hover:bg-red-500 text-white font-bold rounded-lg transition flex items-center justify-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Status badge -->
                                            @if($mediaAnalysis)
                                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold border
                                                    @if($mediaAnalysis->status === 'completed') bg-green-50 text-green-700 border-green-200
                                                    @elseif($mediaAnalysis->status === 'failed') bg-red-50 text-red-700 border-red-200
                                                    @elseif($mediaAnalysis->status === 'processing') bg-blue-50 text-blue-700 border-blue-200 animate-pulse
                                                    @else bg-amber-50 text-amber-700 border-amber-200
                                                    @endif">
                                                    @if($mediaAnalysis->status === 'completed') Đã phân tích
                                                    @elseif($mediaAnalysis->status === 'failed') Lỗi — thử lại
                                                    @elseif($mediaAnalysis->status === 'processing') Đang xử lý...
                                                    @else Chờ
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-slate-400">
                                    <div class="text-4xl mb-3">📸</div>
                                    <p class="text-sm font-medium">Chưa có ảnh tem nhãn thiết bị</p>
                                    <p class="text-xs mt-1">Chụp ảnh tem thông số kỹ thuật trên thiết bị và tải lên để AI tự động trích xuất công suất.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- AI Analysis Results -->
                    @php $latestCompleted = $analyses->firstWhere('status', 'completed'); @endphp
                    @if($latestCompleted && $latestCompleted->result)
                        <div class="glass-panel rounded-2xl border border-accent-200/60 shadow-sm bg-white/70 overflow-hidden" x-data="{}">
                            <div class="px-6 py-4.5 border-b border-accent-100 bg-accent-50/60 flex justify-between items-center">
                                <h3 class="font-bold text-slate-800 font-outfit flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-accent-500 text-white flex items-center justify-center text-xs">AI</span>
                                    Kết quả phân tích AI
                                </h3>
                                <span class="text-xs font-semibold text-accent-600">Độ tin cậy: {{ round($latestCompleted->result->confidence * 100) }}%</span>
                            </div>
                            <div class="p-6">
                                <p class="text-xs text-slate-500 mb-4">AI đã trích xuất các thông số từ ảnh. Kiểm tra từng trường và nhấn <strong>"Áp dụng"</strong> để đưa vào thiết bị.</p>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50/80">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Trường</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Giá trị AI đề xuất</th>
                                                <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase">Độ tin cậy</th>
                                                <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase">Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($latestCompleted->result->extractions as $extraction)
                                                @php
                                                    $fieldMap = [
                                                        'device_type' => 'Loại thiết bị',
                                                        'brand' => 'Thương hiệu',
                                                        'model' => 'Model',
                                                        'rated_power' => 'Công suất định mức (W)',
                                                        'max_power' => 'Công suất tối đa (W)',
                                                        'standby_power' => 'Công suất chờ (W)',
                                                        'voltage' => 'Điện áp (V)',
                                                        'current' => 'Cường độ dòng điện (A)',
                                                        'capacity' => 'Dung tích',
                                                    ];
                                                @endphp
                                                <tr class="hover:bg-slate-50/50 transition">
                                                    <td class="px-6 py-3 text-sm font-semibold text-slate-700">{{ $fieldMap[$extraction->field] ?? $extraction->field }}</td>
                                                    <td class="px-6 py-3 text-sm font-bold text-slate-800">{{ $extraction->ai_value ?? '—' }}</td>
                                                    <td class="px-6 py-3 text-center">
                                                        @if($extraction->confidence)
                                                            <span class="text-xs font-bold @if($extraction->confidence >= 0.8) text-green-600 @elseif($extraction->confidence >= 0.5) text-amber-600 @else text-red-600 @endif">
                                                                {{ round($extraction->confidence * 100) }}%
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-3 text-center">
                                                        @if(!$extraction->isConfirmed() && $extraction->ai_value)
                                                            <form method="POST" action="{{ route('ai.extractions.confirm', $extraction) }}" class="inline-flex items-center gap-1.5">
                                                                @csrf
                                                                <input type="hidden" name="confirmed_value" value="{{ $extraction->ai_value }}" />
                                                                <button type="submit" class="text-xs px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white font-bold rounded-lg transition shadow-sm">
                                                                    Áp dụng
                                                                </button>
                                                            </form>
                                                        @elseif($extraction->isConfirmed())
                                                            <span class="text-xs font-bold text-green-600 flex items-center gap-1 justify-center">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                Đã áp dụng
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-slate-400">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4 p-3 bg-amber-50/80 border border-amber-200 rounded-xl text-xs text-amber-700">
                                    <strong>Lưu ý:</strong> Sau khi áp dụng, giá trị sẽ được lưu vào Device Extraction. Để cập nhật chính thức vào thiết bị, vào trang <a href="{{ route('devices.edit', $device) }}" class="text-primary-600 font-bold underline">Chỉnh sửa thiết bị</a>.
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($analyses->where('status', 'processing')->isNotEmpty())
                        <div class="glass-panel rounded-2xl border border-blue-200/60 shadow-sm bg-blue-50/40 p-6 text-center">
                            <div class="w-8 h-8 border-4 border-blue-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                            <p class="text-sm font-semibold text-blue-700">AI đang phân tích ảnh...</p>
                            <p class="text-xs text-blue-500 mt-1">Quá trình có thể mất 5-15 giây. Trang sẽ tự cập nhật khi hoàn tất.</p>
                        </div>
                    @endif
                </div>

                <!-- Cột 3: Device Info + Specs + Profile -->
                <div class="space-y-6">
                    <!-- Device Info -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">Thông tin thiết bị</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Loại máy</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->deviceType?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Thương hiệu</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->brand ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Model</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->model ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Serial</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->serial ?? '—' }}</dd></div>
                            <div class="flex justify-between">
                                <dt class="text-xs font-bold text-slate-400 uppercase">Trạng thái</dt>
                                <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">{{ $device->status }}</span></dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Power Specs Card -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">Thông số công suất</h3>
                        @if($device->specification)
                            <dl class="space-y-3">
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Công suất định mức</dt><dd class="text-sm font-bold text-primary-700">{{ $device->specification->rated_power }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Công suất tối đa</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->max_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Công suất chờ</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->standby_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Điện áp</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->voltage ?? '—' }} V</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Dòng điện</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->current ?? '—' }} A</dd></div>
                            </dl>
                        @else
                            <div class="text-center py-6 text-slate-400">
                                <div class="text-3xl mb-2">⚡</div>
                                <p class="text-sm font-medium">Chưa có thông số</p>
                                <p class="text-xs mt-1">Tải ảnh tem nhãn lên và dùng AI để trích xuất tự động.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Usage Profile Card -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">Tần suất hoạt động</h3>
                        @if($device->usageProfile)
                            <dl class="space-y-3">
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Giờ/ngày</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->hours_per_day }} giờ</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Ngày/tuần</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->days_per_week }} ngày</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Hệ số tải</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->duty_cycle ? ($device->usageProfile->duty_cycle * 100).'%' : '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">Nguồn</dt><dd class="text-xs font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded border border-primary-200 capitalize">{{ $device->usageProfile->source }}</dd></div>
                            </dl>
                        @else
                            <div class="text-center py-6 text-slate-400">
                                <div class="text-3xl mb-2">📅</div>
                                <p class="text-sm font-medium">Chưa có tần suất sử dụng</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
