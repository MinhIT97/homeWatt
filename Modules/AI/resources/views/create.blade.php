<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('ai.create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('ai.analyses.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="media_id" value={{ __('ai.select_image') }} />
                        <select id="media_id" name="media_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="">{{ __('ai.select_image_option') }}</option>
                            @foreach($media as $m)
                                <option value="{{ $m->id }}">Ảnh #{{ $m->id }} — {{ $m->mime_type }} ({{ number_format($m->size / 1024, 1) }} KB)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('media_id')" class="mt-2" />
                        @if($media->isEmpty())
                            <p class="text-xs text-amber-600 mt-2">{{ __('ai.upload_first_note') }}</p>
                        @endif
                    </div>

                    <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                        <p class="font-semibold mb-1">{{ __('ai.ai_note') }}</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>{{ __('ai.ai_note_clear') }}</li>
                            <li>{{ __('ai.ai_note_suggestion') }}</li>
                            <li>{{ __('ai.ai_note_token') }}</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('ai.analyses.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('ai.start_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
