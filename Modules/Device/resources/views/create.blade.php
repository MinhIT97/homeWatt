<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('device.create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('devices.store') }}" class="p-8 space-y-6"
                      x-data="{
                          name: '{{ old('name') }}',
                          deviceTypeId: '{{ old('device_type_id') }}',
                          brand: '{{ old('brand') }}',
                          model: '{{ old('model') }}',
                          location: '{{ old('location') }}',
                          ratedPower: '{{ old('rated_power') }}',
                          maxPower: '{{ old('max_power') }}',
                          standbyPower: '{{ old('standby_power') }}',
                          voltage: '{{ old('voltage') }}',
                          warrantyDuration: '{{ old('warranty_duration') }}',
                          warrantyUnit: '{{ old('warranty_unit', 'month') }}',
                          maintenanceInterval: '{{ old('maintenance_interval') }}',
                          lastMaintainedAt: '{{ old('last_maintained_at') }}',
                          isAnalyzing: false,
                          statusMessage: '',
                          statusType: '',

                           populateForm(data) {
                               if (!data.brand && !data.model && !data.rated_power_watts && !data.voltage) {
                                   this.statusMessage = 'AI không thể đọc được thông số từ ảnh này. Vui lòng chụp/chọn ảnh nhãn mác rõ nét hơn.';
                                   this.statusType = 'error';
                                   return;
                               }

                               this.brand = data.brand || '';
                               this.model = data.model || '';
                               this.ratedPower = data.rated_power_watts || '';
                               this.maxPower = data.max_power_watts || '';
                               this.standbyPower = data.standby_power_watts || '';
                               this.voltage = data.voltage || '';

                               // Auto-detect device type from options by data-slug
                               if (data.device_type) {
                                   const typeOption = Array.from(document.querySelectorAll('#device_type_id option')).find(opt => 
                                       opt.getAttribute('data-slug') === data.device_type
                                   );
                                   if (typeOption) {
                                       this.deviceTypeId = typeOption.value;
                                   }
                               }

                               // Set default name if name is empty
                               if (!this.name) {
                                   this.name = (this.brand ? this.brand + ' ' : '') + (data.device_type ? data.device_type.replace('_', ' ').toUpperCase() : 'Thiết bị');
                               }

                               this.statusMessage = 'Đã trích xuất thông số thành công! Vui lòng kiểm tra lại biểu mẫu.';
                               this.statusType = 'success';
                           },

                           analyzeImage(event) {
                               const file = event.target.files[0];
                               if (!file) return;

                               this.isAnalyzing = true;
                               this.statusMessage = 'Đang tải ảnh và gửi yêu cầu phân tích...';
                               this.statusType = '';

                               const formData = new FormData();
                               formData.append('image', file);

                               fetch('{{ route('devices.analyze-image') }}', {
                                   method: 'POST',
                                   headers: {
                                       'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                   },
                                   body: formData
                               })
                               .then(response => {
                                   if (!response.ok) {
                                       throw new Error('Lỗi từ máy chủ AI hoặc vượt quá giới hạn cuộc gọi.');
                                   }
                                   return response.json();
                               })
                               .then(res => {
                                   console.log('AI Extraction Response (Initiated):', res);
                                   if (res.success && res.async) {
                                       const analysisId = res.analysis_id;
                                       this.statusMessage = 'AI đang quét thông số thiết bị trong nền. Bạn có thể tiếp tục điền biểu mẫu...';
                                       
                                       // Polling fallback
                                       const pollInterval = setInterval(() => {
                                           fetch(`/devices/analysis/${analysisId}/status`)
                                               .then(r => r.json())
                                               .then(statusRes => {
                                                   if (statusRes.status === 'completed') {
                                                       clearInterval(pollInterval);
                                                       this.isAnalyzing = false;
                                                       this.populateForm(statusRes.data);
                                                   } else if (statusRes.status === 'failed') {
                                                       clearInterval(pollInterval);
                                                       this.isAnalyzing = false;
                                                       this.statusMessage = statusRes.message || 'Không thể trích xuất được thông số từ hình ảnh.';
                                                       this.statusType = 'error';
                                                   }
                                               })
                                               .catch(err => {
                                                   console.error('Polling error:', err);
                                               });
                                       }, 2000);

                                       // WebSocket / Echo integration
                                       if (window.Echo) {
                                           window.Echo.channel('device-analysis')
                                               .listen('.scanned', (e) => {
                                                   if (e.analysisId === analysisId) {
                                                       clearInterval(pollInterval);
                                                       fetch(`/devices/analysis/${analysisId}/status`)
                                                           .then(r => r.json())
                                                           .then(statusRes => {
                                                               this.isAnalyzing = false;
                                                               if (statusRes.status === 'completed') {
                                                                   this.populateForm(statusRes.data);
                                                               } else {
                                                                   this.statusMessage = statusRes.message || 'Phân tích ảnh thất bại.';
                                                                   this.statusType = 'error';
                                                               }
                                                           })
                                                           .catch(err => {
                                                               console.error('Status fetch error:', err);
                                                               this.isAnalyzing = false;
                                                               this.statusMessage = 'Không thể tải kết quả phân tích ảnh.';
                                                               this.statusType = 'error';
                                                           });
                                                   }
                                               });
                                       }
                                   } else {
                                       throw new Error(res.message || 'Không thể khởi động tiến trình phân tích ảnh.');
                                   }
                               })
                               .catch(err => {
                                   console.error(err);
                                   this.statusMessage = err.message;
                                   this.statusType = 'error';
                                   this.isAnalyzing = false;
                               });
                           }
                      }">
                    @csrf

                    <!-- AI Scan section -->
                    <div class="p-5 bg-gradient-to-br from-primary-50 to-primary-100/50 rounded-2xl border border-primary-100 space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="text-3xl p-3 bg-white rounded-xl shadow-sm">🤖</span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">Quét thông số tự động bằng AI</h3>
                                <p class="text-xs text-slate-500 mt-1">Chụp ảnh nhãn mác hoặc tem công suất của thiết bị để AI tự động điền các thông số kỹ thuật.</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3 items-center">
                            <label class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl text-sm font-semibold text-slate-700 cursor-pointer shadow-sm transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Chụp ảnh / Chọn ảnh</span>
                                <input type="file" accept="image/*" capture="environment" class="hidden" @change="analyzeImage($event)">
                            </label>
                            
                            <div x-show="isAnalyzing" class="flex items-center gap-2 text-xs text-slate-500 animate-pulse">
                                <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="statusMessage"></span>
                            </div>

                            <div x-show="!isAnalyzing && statusMessage" class="text-xs font-semibold px-3 py-1.5 rounded-lg border"
                                 :class="statusType === 'success' ? 'bg-green-50 text-green-750 border-green-200' : 'bg-red-50 text-red-750 border-red-200'"
                                 x-text="statusMessage">
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="room_id" value="{{ __('device.room_label') }}" />
                        <select id="room_id" name="room_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="">{{ __('device.select_room') }}</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId) == $room->id)>{{ $room->home->name }} / {{ $room->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="{{ __('device.name_label') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" x-model="name" required placeholder="{{ __('device.name_placeholder') }}" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="device_type_id" value="{{ __('device.type_label') }}" />
                            <select id="device_type_id" name="device_type_id" x-model="deviceTypeId" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="">{{ __('device.select_type') }}</option>
                                @foreach($deviceTypes as $type)
                                    <option value="{{ $type->id }}" data-slug="{{ $type->slug }}">{{ $type->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="brand" value="{{ __('device.brand_label') }}" />
                            <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" x-model="brand" placeholder="{{ __('device.brand_placeholder') }}" />
                        </div>
                        <div>
                            <x-input-label for="model" value="{{ __('device.model_label') }}" />
                            <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" x-model="model" placeholder="{{ __('device.model_placeholder') }}" />
                        </div>
                        <div>
                            <x-input-label for="location" value="{{ __('device.location_label') }}" />
                            <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" x-model="location" placeholder="{{ __('device.location_placeholder') }}" />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">{{ __('device.purchase_info') }}</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="purchase_price" value="{{ __('device.purchase_price') }}" />
                                <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('purchase_price')" placeholder="{{ __('device.purchase_price_placeholder') }}" />
                                <x-input-error :messages="$errors->get('purchase_price')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="purchased_at" value="{{ __('device.purchased_at_label') }}" />
                                <x-text-input id="purchased_at" name="purchased_at" type="date" class="mt-1 block w-full" :value="old('purchased_at')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                            <div>
                                <x-input-label for="warranty_duration" value="Thời hạn bảo hành" />
                                <div class="flex gap-2">
                                    <x-text-input id="warranty_duration" name="warranty_duration" type="number" min="0" class="mt-1 block w-full" x-model="warrantyDuration" placeholder="Ví dụ: 12, 2..." />
                                    <select id="warranty_unit" name="warranty_unit" x-model="warrantyUnit" class="mt-1 block bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 w-32">
                                        <option value="month">Tháng</option>
                                        <option value="year">Năm</option>
                                    </select>
                                </div>
                                <x-input-error :messages="$errors->get('warranty_duration')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6 border-t border-slate-100/60 pt-6">
                            <div>
                                <x-input-label for="maintenance_interval" value="Chu kỳ bảo dưỡng định kỳ (tháng)" />
                                <x-text-input id="maintenance_interval" name="maintenance_interval" type="number" min="1" class="mt-1 block w-full" x-model="maintenanceInterval" placeholder="Ví dụ: 6, 12... (để trống nếu không cần)" />
                                <x-input-error :messages="$errors->get('maintenance_interval')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_maintained_at" value="Ngày bảo dưỡng gần nhất" />
                                <x-text-input id="last_maintained_at" name="last_maintained_at" type="date" class="mt-1 block w-full" x-model="lastMaintainedAt" />
                                <x-input-error :messages="$errors->get('last_maintained_at')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">{{ __('device.power_specs') }}</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="rated_power" value="{{ __('device.rated_power') }}" />
                                <x-text-input id="rated_power" name="rated_power" type="number" step="0.01" class="mt-1 block w-full" x-model="ratedPower" placeholder="{{ __('device.rated_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="max_power" value="{{ __('device.max_power') }}" />
                                <x-text-input id="max_power" name="max_power" type="number" step="0.01" class="mt-1 block w-full" x-model="maxPower" placeholder="{{ __('device.max_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="standby_power" value="{{ __('device.standby_power') }}" />
                                <x-text-input id="standby_power" name="standby_power" type="number" step="0.01" class="mt-1 block w-full" x-model="standbyPower" placeholder="{{ __('device.standby_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="voltage" value="{{ __('common.voltage') }} (V)" />
                                <x-text-input id="voltage" name="voltage" type="number" step="0.1" class="mt-1 block w-full" x-model="voltage" placeholder="{{ __('device.voltage_placeholder') }}" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">{{ __('device.usage_frequency') }}</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="hours_per_day" value="{{ __('device.hours_per_day') }}" />
                                <x-text-input id="hours_per_day" name="hours_per_day" type="number" step="0.5" max="24" class="mt-1 block w-full" :value="old('hours_per_day')" placeholder="{{ __('device.hours_per_day_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="days_per_week" value="{{ __('device.days_per_week') }}" />
                                <x-text-input id="days_per_week" name="days_per_week" type="number" min="1" max="7" class="mt-1 block w-full" :value="old('days_per_week', 7)" />
                            </div>
                            <div>
                                <x-input-label for="duty_cycle" value="{{ __('device.load_factor') }}" />
                                <x-text-input id="duty_cycle" name="duty_cycle" type="number" step="0.01" min="0" max="1" class="mt-1 block w-full" :value="old('duty_cycle')" placeholder="{{ __('device.load_factor_placeholder') }}" />
                                <p class="text-[11px] text-slate-400 mt-1">{{ __('device.load_factor_desc') }}</p>
                            </div>
                            <div>
                                <x-input-label for="season" value="{{ __('device.season') }}" />
                                <select id="season" name="season" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm">
                                    <option value="all">{{ __('device.season_all_year') }}</option>
                                    <option value="summer">{{ __('device.season_summer') }}</option>
                                    <option value="winter">{{ __('device.season_winter') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('devices.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('device.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
