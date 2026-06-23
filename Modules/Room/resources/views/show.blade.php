<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ $room->name }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('rooms.edit', $room) }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 hover:border-slate-400 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('{{ __('room.delete_confirm') }}')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center px-4 py-2 border border-red-200 text-red-650 hover:bg-red-50 rounded-xl text-sm font-semibold transition">{{ __('room.delete_button') }}</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.type_label') }}</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ __("room.types.{$room->type}") }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.floor_label') }}</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $room->floor ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.home_label') }}</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $room->home->name }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('room.device_count') }}</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $room->devices->count() }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
