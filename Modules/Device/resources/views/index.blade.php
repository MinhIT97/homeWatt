<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Danh Sách Thiết Bị</h2>
            <a href="{{ route('devices.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform w-full sm:w-auto text-center">
                + Thêm thiết bị
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($devices->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm p-12 text-center bg-white/70">
                    <div class="text-5xl mb-4">🔌</div>
                    <p class="text-slate-500 text-sm mb-4">Chưa có thiết bị nào được kết nối.</p>
                    <a href="{{ route('devices.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                        Thêm thiết bị đầu tiên
                    </a>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <!-- Mobile Card List View -->
                    <div class="block sm:hidden divide-y divide-slate-100">
                        @foreach($devices as $device)
                            <a href="{{ route('devices.show', $device) }}" class="block p-4 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <div>
                                        <span class="text-sm font-bold text-slate-800 font-outfit">{{ $device->name }}</span>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $device->brand }} {{ $device->model }}</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border shrink-0 {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ $device->status }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500 mt-2">
                                    <span class="font-medium text-slate-600">
                                        {{ $device->deviceType?->name }} • {{ $device->room->name }}
                                    </span>
                                    <span class="font-semibold text-accent-650">
                                        {{ $device->specification?->rated_power ? $device->specification->rated_power.' W' : '—' }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Thiết bị</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Loại</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Phòng</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Công suất</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($devices as $device)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('devices.show', $device) }}" class="text-sm font-bold text-slate-800 hover:text-primary-600 transition">{{ $device->name }}</a>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $device->brand }} {{ $device->model }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">{{ $device->deviceType?->name }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">{{ $device->room->name }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-semibold text-accent-600">
                                            {{ $device->specification?->rated_power ? $device->specification->rated_power.' W' : '—' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $device->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                                {{ $device->status }}
                                            </span>
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
