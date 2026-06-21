<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $home->name }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('homes.edit', $home) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">Chỉnh sửa</a>
                <a href="{{ route('homes.members', $home) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">Thành viên</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <div class="p-6">
                    <dl class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Địa chỉ</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $home->address ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Chủ sở hữu</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $home->owner->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Múi giờ</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $home->timezone }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tiền tệ</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $home->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Thành viên</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $home->members->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng thái</dt>
                            <dd class="mt-1">
                                <span class="capitalize px-2.5 py-1 rounded-full text-xs font-semibold border {{ $home->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $home->status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Rooms section -->
            <div class="mt-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-slate-800 font-outfit">Danh Sách Phòng</h3>
                    <a href="#" class="text-sm text-primary-600 hover:text-primary-800 font-bold transition flex items-center gap-1">+ Thêm phòng</a>
                </div>

                @if($home->rooms->isEmpty())
                    <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-8 text-center">
                        <p class="text-slate-500 text-sm">Chưa có phòng nào. Thêm phòng để sắp xếp các thiết bị của bạn.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($home->rooms as $room)
                            <div class="glass-panel rounded-xl border border-slate-200/60 shadow-sm p-5 bg-white/70 hover:border-primary-200 hover:-translate-y-0.5 transition duration-150 transform">
                                <h4 class="font-bold text-slate-850">{{ $room->name }}</h4>
                                <p class="text-xs text-slate-500 mt-1 capitalize">{{ $room->type }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
