<nav x-data="{ open: false }" class="backdrop-blur-md bg-white/70 border-b border-slate-200/50 sticky top-0 z-50 select-none h-16 flex items-center justify-between px-6">
    <!-- Mobile Menu Toggle & Brand Logo -->
    <div class="flex items-center gap-4 sm:hidden">
        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none transition">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <a href="{{ route('dashboard') }}" class="text-lg font-extrabold flex items-center gap-1.5">
            <span class="p-1 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg text-white shadow-md shadow-blue-500/10">⚡</span>
            <span class="text-gradient-primary-accent font-outfit">HomeWatt</span>
        </a>
    </div>

    <!-- Spacer on Desktop -->
    <div class="hidden sm:block"></div>

    <!-- Right Side Actions: Notification Bell + User Profile -->
    <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        <button class="relative p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition duration-150">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full text-[9px] font-extrabold text-white flex items-center justify-center">3</span>
        </button>

        <!-- Divider -->
        <div class="h-6 w-[1px] bg-slate-200/80"></div>

        <!-- User Profile Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2.5 hover:bg-slate-50 p-1.5 rounded-xl transition duration-150 text-slate-700">
                    <!-- Avatar -->
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-cyan-400 text-white flex items-center justify-center text-xs font-bold font-outfit shadow-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <!-- User name -->
                    <div class="hidden sm:block text-start">
                        <p class="text-xs font-bold text-slate-800 leading-none font-outfit">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-semibold leading-none mt-1">Người dùng</p>
                    </div>
                    <!-- Arrow icon -->
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden fixed inset-y-0 left-0 w-64 bg-white shadow-2xl z-50 transition-transform duration-300 transform sm:hidden">
        <!-- Close button & Logo -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="p-1 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg text-white shadow-md shadow-blue-500/10">⚡</span>
                <span class="text-lg font-bold text-slate-800 font-outfit">HomeWatt</span>
            </a>
            <button @click="open = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Links in Mobile -->
        <div class="py-4 px-4 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Tổng quan
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('homes.index')" :active="request()->routeIs('homes.*')">
                Ngôi nhà
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('devices.index')" :active="request()->routeIs('devices.*')">
                Thiết bị
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.*')">
                Phòng
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ai.analyses.index')" :active="request()->routeIs('ai.*')">
                AI Nhận diện
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('energy.index')" :active="request()->routeIs('energy.*')">
                Thống kê
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tariff.index')" :active="request()->routeIs('tariff.*')">
                Hóa đơn điện
            </x-responsive-nav-link>
        </div>
    </div>
    
    <!-- Mobile Drawer Overlay -->
    <div x-show="open" @click="open = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 sm:hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>
</nav>
