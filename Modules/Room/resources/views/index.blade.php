<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Danh Sách Phòng</h2>
            <a href="{{ route('rooms.create') }}" class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform">
                + Thêm phòng
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($rooms->isEmpty())
                <div class="glass-panel rounded-3xl border border-slate-200/60 shadow-sm p-12 text-center max-w-md mx-auto">
                    <div class="text-6xl mb-6 animate-float">🚪</div>
                    <h3 class="text-xl font-bold text-slate-800 font-outfit mb-2">Chưa có phòng nào</h3>
                    <p class="text-slate-500 text-sm mb-6">Tạo phòng đầu tiên để sắp xếp thiết bị theo không gian.</p>
                    <a href="{{ route('rooms.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform">
                        Thêm phòng đầu tiên
                    </a>
                </div>
            @else
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($rooms as $room)
                        <a href="{{ route('rooms.show', $room) }}" class="block glass-panel rounded-2xl border border-slate-200/60 hover:border-primary-300 hover:shadow-lg hover:-translate-y-1 transform transition duration-300 bg-white/70">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <h3 class="font-extrabold text-lg text-slate-850 font-outfit hover:text-primary-600 transition">{{ $room->name }}</h3>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 capitalize">{{ str_replace('_', ' ', $room->type) }}</span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
                                    <span class="font-medium">{{ $room->home->name }}</span>
                                    @if($room->floor)
                                        <span class="text-xs">Tầng {{ $room->floor }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $rooms->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
