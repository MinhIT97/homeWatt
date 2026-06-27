<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.report_category') }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.monthly') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    {{ __('expense.report_monthly') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- Filter Form --}}
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-6">
                <form method="GET" action="{{ route('reports.category') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <x-input-label for="home_id" :value="__('expense.select_home')" />
                        <select id="home_id" name="home_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2 px-3 text-sm" required>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected($selectedHomeId == $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from" :value="__('expense.filter_from')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full text-sm" :value="$from" required />
                    </div>
                    <div>
                        <x-input-label for="to" :value="__('expense.filter_to')" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full text-sm" :value="$to" required />
                    </div>
                    <div>
                        <x-primary-button class="w-full justify-center">{{ __('common.apply') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if($report)
                {{-- Category stats --}}
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-extrabold text-slate-800 font-outfit text-base">{{ __('Thống kê theo danh mục') }}</h3>
                    </div>
                    <!-- Mobile View (Card List) -->
                    <div class="block sm:hidden divide-y divide-slate-100 bg-white/80">
                        @forelse($report['rows'] as $row)
                            <div class="p-4 space-y-2">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl p-1.5 rounded-lg bg-slate-100/85" style="border-left: 3px solid {{ $row->color ?: '#cbd5e1' }}">{{ $row->icon ?: '🏷️' }}</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $row->name }}</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold {{ $row->type === 'income' ? 'bg-green-50 text-green-700 border border-green-250' : 'bg-red-50 text-red-700 border border-red-250' }}">
                                        {{ $row->type === 'income' ? __('expense.type_income') : __('expense.type_expense') }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-500">
                                    <div>
                                        <span>{{ __('Số lượng GD') }}:</span>
                                        <span class="font-bold text-slate-700">{{ $row->count }}</span>
                                    </div>
                                    <div class="font-extrabold text-sm {{ $row->type === 'income' ? 'text-green-650' : 'text-red-650' }}">
                                        {{ $row->type === 'income' ? '+' : '-' }}{{ number_format($row->total, 0, ',', '.') }} đ
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-500 text-sm">
                                {{ __('common.no_data') }}
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop View (Table) -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/30">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.field') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.type') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Số lượng GD') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('expense.amount_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white/80 divide-y divide-slate-100">
                                @forelse($report['rows'] as $row)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <span class="text-2xl p-2 rounded-xl bg-slate-100/85" style="border-color: {{ $row->color }}">{{ $row->icon ?: '🏷️' }}</span>
                                                <span class="font-bold text-slate-800">{{ $row->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                            <span class="px-2.5 py-1 rounded-lg text-xs {{ $row->type === 'income' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                                {{ $row->type === 'income' ? __('expense.type_income') : __('expense.type_expense') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-bold">
                                            {{ $row->count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold {{ $row->type === 'income' ? 'text-green-650' : 'text-red-650' }}">
                                            {{ $row->type === 'income' ? '+' : '-' }}{{ number_format($row->total, 0, ',', '.') }} đ
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-500 text-sm">
                                            {{ __('common.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
