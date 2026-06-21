<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $plan->name }}</h2>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('tariff.destroy', $plan) }}" onsubmit="return confirm('Xóa biểu giá này?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-500 hover:text-red-700 font-semibold transition">Xóa</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Plan Info -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-6">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nhà cung cấp</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $plan->provider ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Khu vực</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $plan->region ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loại giá</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800 capitalize">{{ $plan->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hiệu lực từ</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $plan->effective_from->format('d/m/Y') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Tiers Table -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40">
                    <h3 class="font-bold text-slate-800 font-outfit">Chi Tiết Các Bậc Giá</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Bậc</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Giới hạn (kWh)</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Đơn giá (đ/kWh)</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Thuế (%)</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Phụ phí (đ)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($plan->tiers->sortBy('tier_number') as $tier)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-800">Bậc {{ $tier->tier_number }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700 text-right font-semibold">{{ $tier->limit_kwh ? number_format($tier->limit_kwh, 2) : 'Không giới hạn' }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700 text-right font-semibold">{{ number_format($tier->rate, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700 text-right">{{ $tier->tax_percent }}%</td>
                                    <td class="px-6 py-4 text-sm text-slate-700 text-right">{{ number_format($tier->surcharge, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
