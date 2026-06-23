<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('device.import_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                <div class="mb-6">
                    <h3 class="font-bold text-slate-800 mb-2">{{ __('device.import_upload') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('device.import_upload_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('devices.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="room_id" value="{{ __('device.room_label') }}" />
                        <select id="room_id" name="room_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="">{{ __('device.select_room') }}</option>
                            @php
                                $rooms = Modules\Room\Models\Room::whereHas('home.members', fn($q) => $q->where('user_id', Auth::id())->whereIn('role', ['owner', 'manager']))->with('home')->get();
                            @endphp
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->home->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="file" value="{{ __('device.import_file_label') }}" />
                        <input id="file" name="file" type="file" accept=".csv,.xlsx,.xls" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100" required>
                        <p class="text-xs text-slate-400 mt-1">{{ __('device.import_file_desc') }}</p>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <x-primary-button class="w-full justify-center">{{ __('device.import_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
