<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('home.members_title') }} — {{ $home->name }}</h2>
            <a href="{{ route('homes.show', $home) }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('home.back_to_detail') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50/80 border border-red-200 text-red-700 rounded-xl text-sm font-medium shadow-sm">{{ session('error') }}</div>
            @endif

            <!-- Invite form -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-850 font-outfit mb-4">{{ __('home.invite_member') }}</h3>
                    <form method="POST" action="{{ route('homes.invite', $home) }}" class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-end">
                        @csrf
                        <div class="flex-1 w-full">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required placeholder="{{ __('home.email_placeholder') }}" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="w-full sm:w-48">
                            <x-input-label for="role" value="{{ __('home.role_label') }}" />
                            <select id="role" name="role" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm">
                                <option value="manager">{{ __('home.role_manager') }}</option>
                                <option value="member" selected>{{ __('home.role_member') }}</option>
                                <option value="viewer">{{ __('home.role_viewer') }}</option>
                            </select>
                        </div>
                        <x-primary-button class="w-full sm:w-auto h-[46px] justify-center">{{ __('home.invite_button') }}</x-primary-button>
                    </form>
                </div>
            </div>

            <!-- Members list -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-850 font-outfit mb-4">{{ __('home.members_list') }}</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($home->members as $member)
                            <div class="py-4 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $member->user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $member->user->email }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border capitalize
                                        @if($member->role === 'owner') bg-amber-50 text-amber-700 border-amber-250
                                        @elseif($member->role === 'manager') bg-blue-50 text-blue-700 border-blue-200
                                        @elseif($member->role === 'viewer') bg-slate-100 text-slate-600 border-slate-200
                                        @else bg-green-50 text-green-700 border-green-200
                                        @endif">
                                        {{ $member->role }}
                                    </span>
                                    @if($member->role !== 'owner')
                                        <form method="POST" action="{{ route('homes.members.remove', [$home, $member]) }}" onsubmit="return confirm('{{ __('common.remove_member_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-red-500 hover:text-red-700 border border-red-200 hover:bg-red-50/50 rounded-xl px-3 py-1.5 transition">{{ __('common.remove') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
