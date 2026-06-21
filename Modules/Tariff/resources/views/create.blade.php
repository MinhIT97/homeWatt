<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Thêm Biểu Giá Điện Mới</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('tariff.store') }}" class="p-8 space-y-6" x-data="{ tiers: [{ tier_number: 1, limit_kwh: 50, rate: 1806, tax_percent: 10, surcharge: 0 }] }">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Tên biểu giá" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="Ví dụ: Giá điện sinh hoạt EVN 2024" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="provider" value="Nhà cung cấp" />
                            <x-text-input id="provider" name="provider" type="text" class="mt-1 block w-full" :value="old('provider')" placeholder="Ví dụ: EVN, TKV,..." />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="region" value="Khu vực" />
                            <x-text-input id="region" name="region" type="text" class="mt-1 block w-full" :value="old('region')" placeholder="Ví dụ: Việt Nam" />
                        </div>
                        <div>
                            <x-input-label for="type" value="Loại giá" />
                            <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition py-2.5 px-3.5">
                                <option value="residential">Sinh hoạt</option>
                                <option value="commercial">Kinh doanh</option>
                                <option value="industrial">Sản xuất</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="effective_from" value="Ngày hiệu lực" />
                            <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 block w-full" :value="old('effective_from', now()->format('Y-m-d'))" required />
                        </div>
                    </div>

                    <!-- Tiers -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-slate-800 font-outfit">Bậc giá điện</h3>
                            <button type="button" @click="tiers.push({ tier_number: tiers.length + 1, limit_kwh: null, rate: 3000, tax_percent: 10, surcharge: 0 })" class="text-xs text-primary-600 hover:text-primary-800 font-bold transition">+ Thêm bậc</button>
                        </div>

                        <template x-for="(tier, index) in tiers" :key="index">
                            <div class="grid grid-cols-5 gap-3 mb-3 p-4 bg-slate-50/80 rounded-xl border border-slate-100">
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Bậc</label>
                                    <input type="number" :name="'tiers[' + index + '][tier_number]'" x-model="tier.tier_number" class="mt-1 block w-full text-sm border border-slate-300 rounded-lg px-2 py-1.5" readonly />
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Giới hạn (kWh)</label>
                                    <input type="number" step="0.01" :name="'tiers[' + index + '][limit_kwh]'" x-model="tier.limit_kwh" class="mt-1 block w-full text-sm border border-slate-300 rounded-lg px-2 py-1.5" placeholder="Bỏ trống = không giới hạn" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Đơn giá (đ/kWh)</label>
                                    <input type="number" step="0.01" :name="'tiers[' + index + '][rate]'" x-model="tier.rate" class="mt-1 block w-full text-sm border border-slate-300 rounded-lg px-2 py-1.5" required />
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase">Thuế (%)</label>
                                    <input type="number" step="0.01" :name="'tiers[' + index + '][tax_percent]'" x-model="tier.tax_percent" class="mt-1 block w-full text-sm border border-slate-300 rounded-lg px-2 py-1.5" />
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="tiers.splice(index, 1)" x-show="tiers.length > 1" class="text-xs text-red-500 hover:text-red-700 font-bold">Xóa</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('tariff.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Lưu biểu giá</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
