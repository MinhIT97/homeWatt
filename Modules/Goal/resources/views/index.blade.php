<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight font-outfit">
                    Mục tiêu tài chính
                </h2>
                <p class="text-xs text-slate-500 mt-1">Theo dõi tiến độ các mục tiêu của bạn</p>
            </div>
            @if($homes->isNotEmpty())
                <div class="flex items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="home_id" onchange="this.form.submit()" class="bg-white border-slate-200 rounded-xl shadow-sm text-xs focus:border-blue-500 focus:ring-blue-500/20 pl-3 pr-8 py-2 font-bold text-slate-700 transition">
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('goal.create') }}" class="flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-500 transition shadow-sm">
                        + Mục tiêu mới
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($goals->isEmpty())
                <div class="text-center py-16">
                    <span class="text-5xl mb-4 block">🎯</span>
                    <h3 class="text-lg font-bold text-slate-700 mb-2">Chưa có mục tiêu nào</h3>
                    <p class="text-sm text-slate-500 mb-6">Tạo mục tiêu đầu tiên để bắt đầu theo dõi tiến độ tài chính.</p>
                    @if($selectedHomeId)
                        <a href="{{ route('goal.create') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-500 transition shadow-md">
                            + Tạo mục tiêu
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($goals as $goal)
                        @php
                            $pct = $goal->percentage();
                            $typeLabels = [
                                'savings' => 'Tiết kiệm',
                                'debt_payoff' => 'Trả nợ',
                                'energy_reduction' => 'Giảm điện',
                                'expense_limit' => 'Hạn mức chi',
                                'income_target' => 'Mục tiêu thu',
                            ];
                            $typeColors = [
                                'savings' => 'from-emerald-500 to-green-400',
                                'debt_payoff' => 'from-red-500 to-orange-400',
                                'energy_reduction' => 'from-yellow-500 to-amber-400',
                                'expense_limit' => 'from-purple-500 to-violet-400',
                                'income_target' => 'from-blue-500 to-cyan-400',
                            ];
                            $colorClass = $typeColors[$goal->type] ?? 'from-blue-500 to-cyan-400';
                        @endphp
                        <a href="{{ route('goal.show', $goal->id) }}" class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition duration-200 block">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $goal->icon ?: '🎯' }}</span>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">{{ $goal->name }}</h4>
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $typeLabels[$goal->type] ?? $goal->type }}</span>
                                    </div>
                                </div>
                                <span @class([
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                    'bg-emerald-100 text-emerald-700' => $goal->status === 'completed',
                                    'bg-blue-100 text-blue-700' => $goal->status === 'active',
                                    'bg-slate-100 text-slate-500' => $goal->status === 'cancelled',
                                ])>
                                    {{ $goal->status === 'completed' ? 'Hoàn thành' : ($goal->status === 'active' ? 'Đang thực hiện' : 'Đã hủy') }}
                                </span>
                            </div>

                            @if($goal->status === 'active')
                                <div class="mb-2">
                                    <div class="flex justify-between text-[10px] font-semibold text-slate-500 mb-1">
                                        <span>{{ number_format($goal->current_amount, 0, ',', '.') }} đ</span>
                                        <span>{{ number_format($goal->target_amount, 0, ',', '.') }} đ</span>
                                    </div>
                                    <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r {{ $colorClass }} transition-all duration-500" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <p class="text-xs font-bold text-slate-600 mt-1 text-center">{{ $pct }}%</p>
                                </div>
                            @else
                                <div class="mb-2">
                                    <div class="flex justify-between text-[10px] font-semibold text-slate-500 mb-1">
                                        <span>{{ number_format($goal->current_amount, 0, ',', '.') }} đ</span>
                                        <span>{{ number_format($goal->target_amount, 0, ',', '.') }} đ</span>
                                    </div>
                                    <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r {{ $colorClass }}" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 mt-1 text-center">{{ $pct }}%</p>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span>{{ $goal->starts_at->format('d/m/Y') }} - {{ $goal->ends_at->format('d/m/Y') }}</span>
                                @if($goal->category)
                                    <span class="text-slate-500">{{ $goal->category->name }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
