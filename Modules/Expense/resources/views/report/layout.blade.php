<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 dark:text-slate-100 tracking-tight font-outfit">
                    {{ __('Báo cáo & Phân tích') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Xem và xuất báo cáo tài chính chi tiết') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($home)
                    <a href="{{ route('reports.export.pdf', request()->query()) }}" class="flex items-center gap-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </a>
                    <a href="{{ route('reports.export.excel', request()->query()) }}" class="flex items-center gap-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Excel
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Report Tabs --}}
            <div class="flex flex-wrap gap-1.5 mb-6 bg-white dark:bg-slate-800 rounded-xl p-1.5 border border-slate-200 dark:border-slate-700 shadow-sm">
                @php
                    $tabs = [
                        'reports.summary' => ['label' => 'Tổng quan', 'icon' => '📊'],
                        'reports.cashflow' => ['label' => 'Dòng tiền', 'icon' => '💸'],
                        'reports.trend' => ['label' => 'Xu hướng', 'icon' => '📈'],
                        'reports.year-comparison' => ['label' => 'So sánh năm', 'icon' => '📅'],
                        'reports.networth' => ['label' => 'Tài sản', 'icon' => '🏦'],
                        'reports.category' => ['label' => 'Danh mục', 'icon' => '🏷️'],
                        'reports.monthly' => ['label' => 'Chi tiết tháng', 'icon' => '📋'],
                    ];
                @endphp
                @foreach($tabs as $route => $tab)
                    <a href="{{ route($route, array_merge(request()->only(['home_id', 'year', 'month']), $route === 'reports.summary' ? [] : [])) }}"
                       class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold transition
                              {{ request()->routeIs($route) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                        <span>{{ $tab['icon'] }}</span>
                        <span class="hidden sm:inline">{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Home Selector --}}
            @if($homes->isNotEmpty())
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="home_id" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 px-3 py-2 font-semibold text-slate-700 dark:text-slate-300">
                            @foreach($homes as $h)
                                <option value="{{ $h->id }}" @selected(($home->id ?? null) == $h->id)>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-app-layout>
