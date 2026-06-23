<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('ai.result_title') }}</h2>
            <a href="{{ route('ai.analyses.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('ai.back') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <!-- Status Card -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800 font-outfit">{{ __('ai.analysis_status') }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ __('ai.provider') }}: {{ $analysis->provider }} / {{ __('ai.model') }}: {{ $analysis->model }}</p>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold capitalize
                        @if($analysis->status === 'completed') bg-green-50 text-green-700 border border-green-200
                        @elseif($analysis->status === 'failed') bg-red-50 text-red-700 border border-red-200
                        @elseif($analysis->status === 'processing') bg-blue-50 text-blue-700 border border-blue-200
                        @else bg-amber-50 text-amber-700 border border-amber-200
                        @endif">
                        {{ $analysis->status }}
                    </span>
                </div>
                @if($analysis->error)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">{{ $analysis->error }}</div>
                @endif
            </div>

            @if($analysis->result)
                <!-- Confidence & Cost -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="glass-panel rounded-xl border border-slate-200/60 p-4 text-center bg-white/70">
                        <p class="text-xs text-slate-400 font-bold uppercase">{{ __('common.confidence') }}</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ round($analysis->result->confidence * 100) }}%</p>
                    </div>
                    <div class="glass-panel rounded-xl border border-slate-200/60 p-4 text-center bg-white/70">
                        <p class="text-xs text-slate-400 font-bold uppercase">{{ __('ai.tokens') }}</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ $analysis->result->prompt_tokens + $analysis->result->completion_tokens }}</p>
                    </div>
                    <div class="glass-panel rounded-xl border border-slate-200/60 p-4 text-center bg-white/70">
                        <p class="text-xs text-slate-400 font-bold uppercase">{{ __('common.cost') }}</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-1">${{ number_format($analysis->result->cost, 6) }}</p>
                    </div>
                </div>

                <!-- Extracted Fields -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40">
                        <h3 class="font-bold text-slate-800 font-outfit">{{ __('ai.extracted_data') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('ai.extracted_data_desc') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('ai.table_field') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('ai.table_ai_value') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('ai.table_confidence') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('ai.table_verify') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($analysis->result->extractions as $extraction)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-3 text-sm font-semibold text-slate-700 capitalize">{{ str_replace('_', ' ', $extraction->field) }}</td>
                                        <td class="px-6 py-3 text-sm text-slate-600">{{ $extraction->ai_value ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm">
                                            @if($extraction->confidence)
                                                <span class="font-semibold @if($extraction->confidence >= 0.8) text-green-600 @elseif($extraction->confidence >= 0.5) text-amber-600 @else text-red-600 @endif">
                                                    {{ round($extraction->confidence * 100) }}%
                                                </span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            @if($extraction->isConfirmed())
                                                <span class="text-green-600 font-semibold">{{ $extraction->confirmed_value }}</span>
                                            @else
                                                <span class="text-slate-400">{{ __('ai.verification_status') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3">
                                            @if(!$extraction->isConfirmed())
                                                <form method="POST" action="{{ route('ai.extractions.confirm', $extraction) }}" class="flex gap-2">
                                                    @csrf
                                                    <input type="text" name="confirmed_value" value="{{ $extraction->ai_value }}" class="w-28 text-xs border border-slate-300 rounded-lg px-2 py-1.5 focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20" />
                                                    <button type="submit" class="text-xs px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white font-semibold rounded-lg transition">{{ __('common.confirm') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
