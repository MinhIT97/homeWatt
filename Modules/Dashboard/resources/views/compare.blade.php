<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('dashboard.compare_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(empty($comparisons))
                <div class="text-center py-12 text-slate-500">{{ __('dashboard.no_homes_compare') }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($comparisons as $c)
                        @php $home = $c['home']; @endphp
                        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-extrabold text-slate-800 text-lg">{{ $home->name }}</h3>
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ ($c['pct_change'] ?? 0) <= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    @if($c['pct_change'] !== null)
                                        {{ $c['pct_change'] <= 0 ? '↓' : '↑' }}{{ abs($c['pct_change']) }}%
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-slate-50 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-extrabold text-slate-900">{{ number_format($c['monthly_kwh'], 1) }}</p>
                                    <p class="text-xs text-slate-500">{{ __('dashboard.kwh_this_month') }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-extrabold text-slate-900">{{ number_format($c['monthly_cost']) }}</p>
                                    <p class="text-xs text-slate-500">{{ __('dashboard.cost_label') }}</p>
                                </div>
                            </div>

                            <div class="space-y-2 text-sm text-slate-600">
                                <div class="flex justify-between">
                                    <span>{{ __('device.table_device') }}</span>
                                    <span class="font-bold">{{ $c['device_count'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('room.room_list') }}</span>
                                    <span class="font-bold">{{ $home->rooms_count }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('home.member_label') }}</span>
                                    <span class="font-bold">{{ $home->members_count }}</span>
                                </div>
                                @if($c['top_device'])
                                <div class="flex justify-between">
                                    <span>{{ __('dashboard.top_device_label') }}</span>
                                    <span class="font-bold text-xs truncate max-w-[140px]">{{ $c['top_device']->device?->name }}</span>
                                </div>
                                @endif
                            </div>

                            <a href="{{ route('dashboard', ['home_id' => $home->id]) }}" class="mt-4 block w-full text-center py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-500 transition">
                                {{ __('home.view_details') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
