<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Import Thiết Bị</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                <div class="mb-6">
                    <h3 class="font-bold text-slate-800 mb-2">Tải lên file CSV/Excel</h3>
                    <p class="text-sm text-slate-500">File cần có các cột: <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">ten_thiet_bi</code> (hoặc <code>name</code>), <code>thuong_hieu</code> (hoặc <code>brand</code>), <code>model</code>, <code>serial</code>.</p>
                </div>

                <form method="POST" action="{{ route('devices.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="room_id" value="Phòng" />
                        <select id="room_id" name="room_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="">Chọn phòng...</option>
                            @php
                                $rooms = Modules\Room\Models\Room::whereHas('home.members', fn($q) => $q->where('user_id', Auth::id())->whereIn('role', ['owner', 'manager']))->with('home')->get();
                            @endphp
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->home->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="file" value="File CSV / Excel" />
                        <input id="file" name="file" type="file" accept=".csv,.xlsx,.xls" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100" required>
                        <p class="text-xs text-slate-400 mt-1">Tối đa 10MB. Định dạng: CSV, XLSX, XLS.</p>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <x-primary-button class="w-full justify-center">Import thiết bị</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
