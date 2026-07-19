@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Notification Preferences</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('notification.preferences.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach ($templates as $template)
                @php
                    $pref = $preferences->get($template->code);
                    $enabled = $pref ? $pref->is_enabled : true;
                    $selectedChannels = $pref ? $pref->channels : $template->channels;
                @endphp

                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">{{ $template->name }}</h2>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="hidden" name="preferences[{{ $loop->index }}][template_code]" value="{{ $template->code }}">
                            <input type="hidden" name="preferences[{{ $loop->index }}][is_enabled]" value="0">
                            <input type="checkbox"
                                   name="preferences[{{ $loop->index }}][is_enabled]"
                                   value="1"
                                   {{ $enabled ? 'checked' : '' }}
                                   class="sr-only peer"
                                   onchange="toggleChannels(this, 'channels-{{ $template->code }}')">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div id="channels-{{ $template->code }}" class="flex flex-wrap gap-3 {{ $enabled ? '' : 'opacity-50 pointer-events-none' }}">
                        @foreach (['mail' => 'Email', 'telegram' => 'Telegram', 'push' => 'Push', 'in_app' => 'In-App'] as $channel => $label)
                            @if (in_array($channel, $template->channels))
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox"
                                           name="preferences[{{ $loop->parent->index }}][channels][]"
                                           value="{{ $channel }}"
                                           {{ in_array($channel, $selectedChannels) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>

                    @if ($template->telegram_body)
                        <details class="mt-3">
                            <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">Preview Telegram message</summary>
                            <pre class="mt-2 text-xs bg-gray-50 p-3 rounded border whitespace-pre-wrap">{{ $template->telegram_body }}</pre>
                        </details>
                    @endif

                    @if ($template->mail_body)
                        <details class="mt-3">
                            <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">Preview Email</summary>
                            <pre class="mt-2 text-xs bg-gray-50 p-3 rounded border whitespace-pre-wrap">{{ $template->mail_body }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                Save Preferences
            </button>
        </div>
    </form>
</div>

<script>
    function toggleChannels(checkbox, containerId) {
        const container = document.getElementById(containerId);
        if (checkbox.checked) {
            container.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            container.classList.add('opacity-50', 'pointer-events-none');
        }
    }
</script>
@endsection
