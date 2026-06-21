<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Biểu Giá Điện & Thuế</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/40">
                    <h3 class="font-bold text-slate-850 font-outfit">Tất cả biểu giá</h3>
                    <a href="{{ route('tariff.create') }}" class="inline-flex items-center px-4 py-2 border border-primary-300 text-primary-600 hover:bg-primary-50 rounded-xl text-sm font-semibold transition">+ Thêm biểu giá</a>
                </div>
                @if($plans->isEmpty())
                    <div class="p-8 text-slate-500 text-sm text-center">Chưa có biểu giá điện nào được tạo.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Tên biểu giá</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nhà cung cấp</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Số bậc/bậc giá</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ngày hiệu lực</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($plans as $plan)
                                    <tr class="hover:bg-slate-50/50 cursor-pointer transition" onclick="location.href='{{ route('tariff.show', $plan) }}'">
                                        <td class="px-6 py-4 text-sm font-bold text-primary-600 hover:text-primary-850">{{ $plan->name }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-medium">{{ $plan->provider ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-semibold">{{ $plan->tiers->count() }} bậc</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 font-semibold">{{ $plan->effective_from->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
