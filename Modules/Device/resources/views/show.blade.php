<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $device->name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ $device->room?->home?->name }} / {{ $device->room?->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('devices.edit', $device) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">{{ __('common.edit') }}</a>
                <a href="{{ route('energy.index', ['device_id' => $device->id]) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white text-sm font-semibold rounded-xl shadow-md transition">{{ __('device.calculate_cost') }}</a>
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
                                {{ __('device.image_label') }}
                            </h3>
                            <span class="text-xs text-slate-400 font-medium">{{ $device->media->count() }} {{ __('device.image_suffix') }}</span>
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
                                            <p class="text-sm font-semibold text-slate-700 group-hover:text-primary-700">{{ __('device.upload_image') }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ __('device.upload_image_desc') }}</p>
                                        </div>
                                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" class="hidden" onchange="this.form.submit()" required />
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
                                            <img src="{{ $media->url() }}" class="w-full h-full object-cover" alt="Device label" loading="lazy" />

                                            <!-- Overlay actions -->
                                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center gap-3">
                                                @if(!$mediaAnalysis || $mediaAnalysis->status === 'failed')
                                                    <form method="POST" action="{{ route('ai.analyses.store') }}">
                                                        @csrf
                                                        <input type="hidden" name="media_id" value="{{ $media->id }}" />
                                                        <button type="submit" title="{{ __('device.ai_analysis') }}" class="w-10 h-10 rounded-full bg-accent-500 hover:bg-accent-400 text-white flex items-center justify-center shadow-lg transition duration-200 transform hover:scale-110">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('devices.delete-image', [$device, $media]) }}" onsubmit="return confirm('{{ __('device.delete_image_confirm') }}')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="{{ __('common.delete') }}" class="w-10 h-10 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-lg transition duration-200 transform hover:scale-110">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Status badge -->
                                            @if($mediaAnalysis)
                                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold border
                                                    @if($mediaAnalysis->status === 'completed') bg-green-50 text-green-700 border-green-200
                                                    @elseif($mediaAnalysis->status === 'failed') bg-red-50 text-red-700 border-red-200
                                                    @elseif($mediaAnalysis->status === 'processing') bg-blue-50 text-blue-700 border-blue-200 animate-pulse
                                                    @else bg-amber-50 text-amber-700 border-amber-200
                                                    @endif">
                                                    @if($mediaAnalysis->status === 'completed') {{ __('device.analyzed') }}
                                                    @elseif($mediaAnalysis->status === 'failed') {{ __('common.error') }}
                                                    @elseif($mediaAnalysis->status === 'processing') {{ __('common.loading') }}
                                                    @else {{ __('common.waiting') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-slate-400">
                                    <div class="text-4xl mb-3">📸</div>
                                    <p class="text-sm font-medium">{{ __('device.no_image') }}</p>
                                    <p class="text-xs mt-1">{{ __('device.no_image_desc') }}</p>
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
                                    {{ __('device.ai_result') }}
                                </h3>
                                <span class="text-xs font-semibold text-accent-600">{{ __('common.confidence') }}: {{ round($latestCompleted->result->confidence * 100) }}%</span>
                            </div>
                            <div class="p-6">
                                <p class="text-xs text-slate-500 mb-4">{{ __('device.ai_result_desc') }}</p>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50/80">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('common.field') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('device.ai_suggested_value') }}</th>
                                                <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase">{{ __('common.confidence') }}</th>
                                                <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase">{{ __('common.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($latestCompleted->result->extractions as $extraction)
                                                @php
                                                    $fieldMap = [
                                                        'device_type' => __('device.field_device_type'),
                                                        'brand' => __('device.field_brand'),
                                                        'model' => __('device.field_model'),
                                                        'rated_power' => __('device.field_rated_power'),
                                                        'max_power' => __('device.field_max_power'),
                                                        'standby_power' => __('device.field_standby_power'),
                                                        'voltage' => __('device.field_voltage'),
                                                        'current' => __('device.field_current'),
                                                        'capacity' => __('device.field_capacity'),
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
                                                                    {{ __('common.apply') }}
                                                                </button>
                                                            </form>
                                                        @elseif($extraction->isConfirmed())
                                                            <span class="text-xs font-bold text-green-600 flex items-center gap-1 justify-center">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                                {{ __('common.applied') }}
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
                                    <strong>{{ __('common.note') }}:</strong> {{ __('device.apply_note') }} <a href="{{ route('devices.edit', $device) }}" class="text-primary-600 font-bold underline">{{ __('common.edit') }}</a>.
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($analyses->where('status', 'processing')->isNotEmpty())
                        <div class="glass-panel rounded-2xl border border-blue-200/60 shadow-sm bg-blue-50/40 p-6 text-center">
                            <div class="w-8 h-8 border-4 border-blue-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                            <p class="text-sm font-semibold text-blue-700">{{ __('device.ai_analyzing') }}</p>
                            <p class="text-xs text-blue-500 mt-1">{{ __('device.ai_analyzing_desc') }}</p>
                        </div>
                    @endif
                    <!-- Repair History Section -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden mt-6" 
                         x-data="{ 
                             showRepairModal: false, 
                             repairCostRaw: '', 
                             repairCostDisplay: '',
                             formatCost(val) {
                                 if (!val) return '';
                                 let clean = val.toString().replace(/[^0-9]/g, '');
                                 clean = clean.replace(/^0+/, '');
                                 if (clean === '') return '';
                                 return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                             },
                             updateCost(val) {
                                 let clean = val.replace(/[^0-9]/g, '');
                                 clean = clean.replace(/^0+/, '');
                                 this.repairCostRaw = clean;
                                 this.repairCostDisplay = this.formatCost(clean);
                             }
                         }">
                        <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800 font-outfit flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Lịch sử sửa chữa
                            </h3>
                            <button @click="showRepairModal = true" type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white text-xs font-bold rounded-xl shadow-md transition">
                                + Ghi nhận sửa chữa
                            </button>
                        </div>

                        <div class="p-6">
                            @if($device->repairs->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50/80">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Ngày sửa</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Nội dung</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">Nơi sửa / Thợ</th>
                                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase">Chi phí</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($device->repairs as $repair)
                                                <tr class="hover:bg-slate-50/50 transition">
                                                    <td class="px-6 py-3 text-sm font-semibold text-slate-700 whitespace-nowrap">{{ $repair->repaired_at?->format('d/m/Y') }}</td>
                                                    <td class="px-6 py-3 text-sm text-slate-600">{{ $repair->description }}</td>
                                                    <td class="px-6 py-3 text-sm text-slate-500">{{ $repair->repairer ?? '—' }}</td>
                                                    <td class="px-6 py-3 text-sm font-bold text-slate-800 text-right whitespace-nowrap">{{ number_format($repair->cost, 0, ',', '.') }} đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8 text-slate-400">
                                    <div class="text-4xl mb-3">🔧</div>
                                    <p class="text-sm font-medium">Chưa có lịch sử sửa chữa nào</p>
                                    <p class="text-xs mt-1">Ghi nhận thông tin sửa chữa và chi phí bảo trì để theo dõi dòng đời thiết bị.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Add Repair Modal -->
                        <div x-show="showRepairModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showRepairModal = false">
                                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
                                </div>
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-150 flex justify-between items-center">
                                        <h3 class="text-lg font-bold text-slate-850 font-outfit">Ghi nhận lịch sử sửa chữa</h3>
                                        <button type="button" @click="showRepairModal = false" class="text-slate-400 hover:text-slate-650">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('devices.repairs.store', $device) }}" class="p-6 space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label for="repaired_at" value="Ngày sửa chữa" />
                                                <x-text-input id="repaired_at" name="repaired_at" type="date" class="mt-1 block w-full" value="{{ now()->format('Y-m-d') }}" required />
                                            </div>
                                            <div>
                                                <x-input-label for="repair_cost_display" value="Chi phí sửa chữa" />
                                                <x-text-input id="repair_cost_display" type="text" class="mt-1 block w-full" x-model="repairCostDisplay" @input="updateCost($event.target.value)" required placeholder="Ví dụ: 200.000" />
                                                <input type="hidden" name="cost" :value="repairCostRaw" />
                                            </div>
                                        </div>
                                        <div>
                                            <x-input-label for="repairer" value="Nơi sửa / Thợ thực hiện" />
                                            <x-text-input id="repairer" name="repairer" type="text" class="mt-1 block w-full" placeholder="Ví dụ: Cửa hàng Điện lạnh, Thợ sửa tại nhà..." />
                                        </div>
                                        <div>
                                            <x-input-label for="description" value="Nội dung sửa chữa" />
                                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full bg-white border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" placeholder="Ví dụ: Thay máy nén, nạp gas điều hòa..." required></textarea>
                                        </div>
                                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                            <button type="button" @click="showRepairModal = false" class="text-sm font-semibold text-slate-500 hover:text-slate-805">{{ __('common.cancel') }}</button>
                                            <x-primary-button>Lưu lại</x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột 3: Device Info + Specs + Profile -->
                <div class="space-y-6">
                    <!-- Device Info -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">{{ __('device.device_info') }}</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.type') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->deviceType?->display_name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.brand') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->brand ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.model') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->model ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.location_label') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->location ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.serial') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->serial ?? '—' }}</dd></div>
                            <div class="flex justify-between">
                                <dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.status') }}</dt>
                                <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">{{ __('common.'.$device->status) }}</span></dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-3 mt-3">
                                <dt class="text-xs font-bold text-slate-400 uppercase">Bảo hành</dt>
                                <dd class="text-right">
                                    @if($device->warranty_duration)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $device->is_under_warranty ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                            {{ $device->is_under_warranty ? 'Còn hạn' : 'Hết hạn' }} ({{ $device->warranty_duration }} {{ $device->warranty_unit === 'year' ? 'năm' : 'tháng' }})
                                        </span>
                                        @if($device->warranty_expires_at)
                                            <p class="text-[10px] text-slate-450 mt-1">Hết hạn: {{ $device->warranty_expires_at->format('d/m/Y') }}</p>
                                        @endif
                                    @else
                                        <span class="text-slate-450 text-xs">Không có</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-3 mt-3">
                                <dt class="text-xs font-bold text-slate-400 uppercase">Bảo dưỡng</dt>
                                <dd class="text-right">
                                    @if($device->maintenance_interval)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $device->is_due_for_maintenance ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                            {{ $device->is_due_for_maintenance ? 'Đến hạn' : 'Chưa đến hạn' }} (Chu kỳ {{ $device->maintenance_interval }} tháng)
                                        </span>
                                        @if($device->next_maintenance_at)
                                            <p class="text-[10px] text-slate-450 mt-1">Tiếp theo: {{ $device->next_maintenance_at->format('d/m/Y') }}</p>
                                        @endif
                                    @else
                                        <span class="text-slate-450 text-xs">Không có</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Power Specs Card -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">{{ __('device.power_info') }}</h3>
                        @if($device->specification)
                            <dl class="space-y-3">
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.rated_power') }}</dt><dd class="text-sm font-bold text-primary-700">{{ $device->specification->rated_power }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.max_power') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->max_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.standby_power') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->standby_power ?? '—' }} W</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.voltage') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->voltage ?? '—' }} V</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.current') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->specification->current ?? '—' }} A</dd></div>
                            </dl>
                        @else
                            <div class="text-center py-6 text-slate-400">
                                <div class="text-3xl mb-2">⚡</div>
                                <p class="text-sm font-medium">{{ __('device.no_power_info') }}</p>
                                <p class="text-xs mt-1">{{ __('device.no_power_info_desc') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Usage Profile Card -->
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                        <h3 class="font-bold text-slate-800 font-outfit text-base mb-4 border-b border-slate-100 pb-2">{{ __('device.usage_info') }}</h3>
                        @if($device->usageProfile)
                            <dl class="space-y-3">
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.hours_per_day') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->hours_per_day }} {{ __('device.hours_suffix') }}</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.days_per_week') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->days_per_week }} {{ __('device.days_suffix') }}</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('device.load_factor') }}</dt><dd class="text-sm font-semibold text-slate-800">{{ $device->usageProfile->duty_cycle ? ($device->usageProfile->duty_cycle * 100).'%' : '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-xs font-bold text-slate-400 uppercase">{{ __('common.source') }}</dt><dd class="text-xs font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded border border-primary-200 capitalize">{{ $device->usageProfile->source }}</dd></div>
                            </dl>
                        @else
                            <div class="text-center py-6 text-slate-400">
                                <div class="text-3xl mb-2">📅</div>
                                <p class="text-sm font-medium">{{ __('device.no_usage_info') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
