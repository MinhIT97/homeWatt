<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('device.device_list') }}</h2>
            <a href="{{ route('devices.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform w-full sm:w-auto text-center">
                {{ __('device.add_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($devices->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm p-12 text-center bg-white/70">
                    <div class="text-5xl mb-4">🔌</div>
                    <p class="text-slate-500 text-sm mb-4">{{ __('device.no_devices') }}</p>
                    <a href="{{ route('devices.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                        {{ __('device.add_first') }}
                    </a>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <!-- Mobile Card List View -->
                    <div class="block sm:hidden divide-y divide-slate-100">
                        @foreach($devices as $device)
                            <div class="p-4 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <div>
                                        <a href="{{ route('devices.show', $device) }}" class="text-sm font-bold text-slate-800 font-outfit hover:text-primary-600 transition">{{ $device->name }}</a>
                                        <p class="text-xs text-slate-400 mt-0.5">                                            {{ $device->brand }} {{ $device->model }}
                                            @if($device->location)
                                                <span class="inline-block text-[10px] bg-slate-150 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200/60 ml-1">📍 {{ $device->location }}</span>
                                            @endif</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border shrink-0 {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ __('common.'.$device->status) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500 mt-3">
                                    <span class="font-medium text-slate-600">
                                        {{ $device->deviceType?->display_name }} • {{ $device->room?->name }}
                                    </span>
                                    <span class="font-semibold text-accent-650">
                                        {{ $device->specification?->rated_power ? $device->specification->rated_power.' W' : '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-2.5 border-t border-slate-100/60">
                                    <a href="{{ route('devices.edit', $device) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 py-1.5 px-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                                        {{ __('common.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-650 hover:text-red-800 py-1.5 px-3 bg-red-50 hover:bg-red-100 rounded-lg transition">
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('device.table_device') }}</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('device.table_type') }}</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('device.table_room') }}</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('device.table_power') }}</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('device.table_status') }}</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($devices as $device)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('devices.show', $device) }}" class="text-sm font-bold text-slate-800 hover:text-primary-600 transition">{{ $device->name }}</a>
                                            <p class="text-xs text-slate-400 mt-0.5">                                            {{ $device->brand }} {{ $device->model }}
                                            @if($device->location)
                                                <span class="inline-block text-[10px] bg-slate-150 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200/60 ml-1">📍 {{ $device->location }}</span>
                                            @endif</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">{{ $device->deviceType?->display_name }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">{{ $device->room?->name }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-semibold text-accent-600">
                                            {{ $device->specification?->rated_power ? $device->specification->rated_power.' W' : '—' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                                {{ __('common.'.$device->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold space-x-3">
                                            <a href="{{ route('devices.edit', $device) }}" class="text-blue-600 hover:text-blue-800 transition">{{ __('common.edit') }}</a>
                                            <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-650 hover:text-red-800 transition">{{ __('common.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">{{ $devices->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
