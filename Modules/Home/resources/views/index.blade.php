<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('home.my_home') }}</h2>
            <a href="{{ route('homes.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform w-full sm:w-auto text-center">
                {{ __('home.add_new') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($homes->isEmpty())
                <div class="glass-panel rounded-3xl border border-slate-200/60 shadow-sm">
                    <div class="p-12 text-center max-w-md mx-auto">
                        <div class="text-6xl mb-6 animate-float">🏠</div>
                        <h3 class="text-xl font-bold text-slate-800 font-outfit mb-2">{{ __('home.no_homes') }}</h3>
                        <p class="text-slate-500 text-sm mb-6">{{ __('home.no_homes_desc') }}</p>
                        <a href="{{ route('homes.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-600/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform">
                            {{ __('home.add_first') }}
                        </a>
                    </div>
                </div>
            @else
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($homes as $home)
                        <a href="{{ route('homes.show', $home) }}" class="block glass-panel rounded-2xl border border-slate-200/60 hover:border-primary-300 hover:shadow-lg hover:-translate-y-1 transform transition duration-300 bg-white/70">
                            <div class="p-6">
                                <h3 class="font-extrabold text-lg text-slate-850 font-outfit hover:text-primary-600 transition">{{ $home->name }}</h3>
                                @if($home->address)
                                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $home->address }}
                                    </p>
                                @endif
                                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
                                    <span class="font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        {{ $home->rooms_count ?? 0 }} {{ __('navigation.rooms') }}
                                    </span>
                                    <span class="capitalize px-2.5 py-1 rounded-full text-xs font-semibold border {{ $home->status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ __('common.'.$home->status) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $homes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
