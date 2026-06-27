<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('wallet.title') }}</h2>
            <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white shadow-sm transition">{{ __('wallet.add_new') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            {{-- Total summary --}}
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng số dư thực tế</div>
                        <div class="mt-1 text-3xl font-extrabold text-primary-600 font-outfit">
                            {{ number_format($totalBalance, 0, ',', '.') }} {{ $homeCurrency }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dư nợ thẻ tín dụng</div>
                        <div class="mt-1 text-2xl font-bold text-red-650 font-outfit">
                            {{ number_format($creditCardDebt, 0, ',', '.') }} {{ $homeCurrency }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.total_opening') }}</div>
                        <div class="mt-1 text-2xl font-bold text-slate-700 font-outfit">
                            {{ number_format($totalOpening, 0, ',', '.') }} {{ $homeCurrency }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('wallet.wallet_count') }}</div>
                        <div class="mt-1 text-2xl font-bold text-slate-700 font-outfit">
                            {{ $wallets->count() }}
                        </div>
                    </div>
                </div>
            </div>

            @if($wallets->isEmpty())
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-12 text-center">
                    <div class="text-5xl mb-4">💰</div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">{{ __('wallet.no_wallets') }}</h3>
                    <p class="text-slate-500 text-sm mb-6">{{ __('wallet.no_wallets_desc') }}</p>
                    <a href="{{ route('wallets.create') }}" class="inline-flex items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 rounded-xl text-sm font-semibold text-white shadow-sm transition">+ {{ __('wallet.create_first') }}</a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($wallets as $wallet)
                        <a href="{{ route('wallets.show', $wallet) }}" class="block glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 hover:shadow-md hover:border-primary-300 transition group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    @if($wallet->icon)
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl" style="background-color: {{ $wallet->color ?? '#e2e8f0' }}20;">
                                            {{ $wallet->icon }}
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="font-bold text-slate-800 group-hover:text-primary-600 transition">{{ $wallet->name }}</h3>
                                        <p class="text-xs text-slate-500">{{ __('wallet.type_'.$wallet->type) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-2xl font-extrabold text-slate-800 font-outfit">
                                {{ number_format($wallet->balance, 0, ',', '.') }} <span class="text-sm font-normal text-slate-500">{{ $wallet->currency }}</span>
                            </div>
                            @if($wallet->description)
                                <p class="text-xs text-slate-400 mt-2 line-clamp-2">{{ $wallet->description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>