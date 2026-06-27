<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.transfer_title') }}</h2>
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white shadow-sm transition">
                {{ __('expense.transfer_add_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium shadow-sm">{{ session('error') }}</div>
            @endif

            @if($transfers->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 p-12 text-center">
                    <div class="text-5xl mb-4">🔄</div>
                    <h3 class="text-lg font-bold mb-2">{{ __('expense.no_expenses') }}</h3>
                    <p class="text-slate-500 text-sm">{{ __('expense.no_expenses_desc') }}</p>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70 overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        @foreach($transfers as $t)
                            <li class="p-4 flex items-center justify-between hover:bg-slate-50">
                                <a href="{{ route('transfers.show', $t) }}" class="flex items-center gap-3 flex-1">
                                    <span class="text-xl">🔄</span>
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $t->description ?: __('expense.transfer_title') }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $t->occurred_at instanceof \Carbon\Carbon ? $t->occurred_at->format('d/m/Y') : \Carbon\Carbon::parse($t->occurred_at)->format('d/m/Y') }} · 
                                            {{ $t->fromWallet?->name }} ➔ {{ $t->toWallet?->name }}
                                            @if($t->fee > 0)
                                                · {{ __('expense.transfer_fee') }}: {{ number_format($t->fee, 0, ',', '.') }} {{ $t->currency }}
                                            @endif
                                        </div>
                                    </div>
                                </a>
                                <div class="flex items-center gap-4">
                                    <div class="font-bold text-slate-700">
                                        {{ number_format((float) $t->amount, 0, ',', '.') }} {{ $t->currency }}
                                    </div>
                                    <form action="{{ route('transfers.destroy', $t) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to reverse this transfer?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mt-6">{{ $transfers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
