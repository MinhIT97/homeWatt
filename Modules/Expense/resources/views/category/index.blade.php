<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.category_title') }}</h2>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white shadow-sm transition">
                {{ __('expense.category_add_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium shadow-sm">{{ session('error') }}</div>
            @endif

            @if($categories->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-12 text-center">
                    <div class="text-5xl mb-4">🏷️</div>
                    <h3 class="text-lg font-bold mb-2">{{ __('expense.category_no_categories') }}</h3>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <!-- Mobile View (Card List) -->
                    <div class="block sm:hidden divide-y divide-slate-100 bg-white/80">
                        @foreach($categories as $c)
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl p-1.5 rounded-lg bg-slate-100/80">{{ $c->icon ?: '🏷️' }}</span>
                                        <div>
                                            <span class="font-bold text-slate-800 text-sm">{{ $c->name }}</span>
                                            @if($c->color)
                                                <span class="inline-block w-2.5 h-2.5 rounded-full ml-1" style="background-color: {{ $c->color }}"></span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold {{ $c->type === 'income' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                        {{ $c->type === 'income' ? __('expense.type_income') : __('expense.type_expense') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500">
                                    <div>
                                        <span>{{ __('expense.select_home') }}:</span>
                                        <span class="font-semibold text-slate-700">{{ $c->home?->name ?: '-' }}</span>
                                    </div>
                                    <div>
                                        @if($c->is_system)
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] border border-slate-200">{{ __('common.system_template') ?? 'Hệ thống' }}</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] border border-blue-200">{{ __('common.template') ?? 'Tùy chỉnh' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 pt-2 border-t border-slate-50">
                                    @if(!$c->is_system)
                                        <a href="{{ route('categories.edit', $c) }}" class="text-xs text-primary-600 hover:text-primary-900 transition font-bold">{{ __('common.edit') }}</a>
                                        <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-900 transition font-bold">{{ __('common.delete') }}</button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 text-xs">{{ __('Locked') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desktop View (Table) -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.name') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.type') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('expense.select_home') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('common.status') }}</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white/80 divide-y divide-slate-100">
                                @foreach($categories as $c)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <span class="text-2xl p-2 rounded-xl bg-slate-100/80">{{ $c->icon ?: '🏷️' }}</span>
                                                <div>
                                                    <span class="font-bold text-slate-800">{{ $c->name }}</span>
                                                    @if($c->color)
                                                        <span class="inline-block w-3 h-3 rounded-full ml-1" style="background-color: {{ $c->color }}"></span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                            <span class="px-2.5 py-1 rounded-lg text-xs {{ $c->type === 'income' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                                {{ $c->type === 'income' ? __('expense.type_income') : __('expense.type_expense') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            {{ $c->home?->name ?: '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                            @if($c->is_system)
                                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs border border-slate-200">{{ __('common.system_template') ?? 'Hệ thống' }}</span>
                                            @else
                                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs border border-blue-200">{{ __('common.template') ?? 'Tùy chỉnh' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                @if(!$c->is_system)
                                                    <a href="{{ route('categories.edit', $c) }}" class="text-primary-600 hover:text-primary-900 transition font-bold">{{ __('common.edit') }}</a>
                                                    <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 transition font-bold">{{ __('common.delete') }}</button>
                                                    </form>
                                                @else
                                                    <span class="text-slate-400 cursor-not-allowed text-xs">{{ __('Locked') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
