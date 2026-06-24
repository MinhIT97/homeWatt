<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('room.edit_title') }}: {{ $room->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('rooms.update', $room) }}" class="p-8 space-y-6">
                    @csrf @method('PUT')

                    <div>
                        <x-input-label for="name" value="{{ __('room.name_label') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $room->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="type" value="{{ __('room.type_label') }}" />
                            <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                @foreach(array_keys(__('room.types')) as $key)
                                    <option value="{{ $key }}" @selected(old('type', $room->type) == $key)>{{ __("room.types.{$key}") }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="floor" value="{{ __('room.floor_label') }}" />
                            <x-text-input id="floor" name="floor" type="number" class="mt-1 block w-full" :value="old('floor', $room->floor)" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('rooms.show', $room) }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('room.edit_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
