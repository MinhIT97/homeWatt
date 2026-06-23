<nav x-data="{ 
    open: false, 
    showInstallBtn: false, 
    initPWA() { 
        window.addEventListener('pwa-install-ready', () => { 
            this.showInstallBtn = true; 
        }); 
        if (window.deferredPrompt) { 
            this.showInstallBtn = true; 
        } 
    }, 
    triggerInstall() { 
        if (window.deferredPrompt) { 
            window.deferredPrompt.prompt(); 
            window.deferredPrompt.userChoice.then((choiceResult) => { 
                if (choiceResult.outcome === 'accepted') { 
                    console.log('Người dùng đã cài đặt ứng dụng'); 
                    this.showInstallBtn = false; 
                } 
                window.deferredPrompt = null; 
            }); 
        } 
    } 
}" x-init="initPWA()" class="backdrop-blur-md bg-white/70 border-b border-slate-200/50 sticky top-0 z-50 select-none h-16 flex items-center justify-between px-3 sm:px-6">
    <!-- Mobile Menu Toggle & Brand Logo -->
    <div class="flex items-center gap-4 lg:hidden">
        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none transition">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <a href="{{ route('dashboard') }}" class="text-lg font-extrabold flex items-center gap-1.5">
            <span class="p-1 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg text-white shadow-md shadow-blue-500/10">⚡</span>
            <span class="text-gradient-primary-accent font-outfit hidden min-[360px]:inline">HomeWatt</span>
        </a>
    </div>

    <!-- Spacer on Desktop -->
    <div class="hidden lg:block"></div>

    <!-- Right Side Actions: Notification Bell + User Profile -->
    <div class="flex items-center gap-2 sm:gap-4">
        <!-- Notification Bell -->
        <button class="relative p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition duration-150">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
        </button>

        <!-- Language Switcher -->
        <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200/60">
            <a href="{{ route('lang.switch', 'vi') }}" class="px-2 py-1 text-[10px] font-bold rounded {{ app()->getLocale() == 'vi' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-400 hover:text-slate-600' }} transition">VI</a>
            <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 text-[10px] font-bold rounded {{ app()->getLocale() == 'en' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-400 hover:text-slate-600' }} transition">EN</a>
        </div>

        <!-- Divider -->
        <div class="h-6 w-[1px] bg-slate-200/80 hidden min-[360px]:block"></div>

        <!-- User Profile Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-1 sm:gap-2.5 hover:bg-slate-50 p-1.5 rounded-xl transition duration-150 text-slate-700">
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
                    <svg class="w-4 h-4 text-slate-400 hidden min-[360px]:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div :class="open ? 'flex' : 'hidden'"
         class="fixed inset-y-0 left-0 w-64 h-full shadow-2xl z-50 flex-col lg:hidden"
         style="background-color: #ffffff; min-height: 100vh;">
        <!-- Close button & Logo -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 shrink-0">
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
        <div class="flex-1 py-4 px-4 space-y-1 overflow-y-auto">
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
            <x-responsive-nav-link :href="route('dashboard.compare')" :active="request()->routeIs('dashboard.compare')">
                So sánh
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                Cài đặt
            </x-responsive-nav-link>

            <!-- Mobile Language Switcher -->
            <div class="pt-4 mt-4 border-t border-slate-100 px-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Ngôn ngữ / Language</p>
                <div class="flex gap-2">
                    <a href="{{ route('lang.switch', 'vi') }}" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg border {{ app()->getLocale() == 'vi' ? 'bg-blue-50 border-blue-200 text-blue-600' : 'border-slate-200 bg-white text-slate-500' }} transition">Tiếng Việt</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg border {{ app()->getLocale() == 'en' ? 'bg-blue-50 border-blue-200 text-blue-600' : 'border-slate-200 bg-white text-slate-500' }} transition">English</a>
                </div>
            </div>

            <!-- PWA Install Button inside Mobile Drawer -->
            <div x-show="showInstallBtn" class="mt-6 p-4 bg-gradient-to-br from-blue-50 to-indigo-50/50 border border-blue-100 rounded-2xl">
                <div class="w-8 h-8 bg-blue-600/10 text-blue-600 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </div>
                <h5 class="text-xs font-bold text-slate-800 font-outfit mb-1">Ứng dụng di động</h5>
                <p class="text-[10px] text-slate-500 leading-relaxed mb-3">Cài đặt HomeWatt lên màn hình chính để truy cập nhanh chóng hơn.</p>
                <button @click="triggerInstall()" class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg shadow-sm transition">
                    Cài đặt ứng dụng
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Drawer Overlay -->
    <div :class="open ? 'block' : 'hidden'" @click="open = false" class="fixed inset-0 z-40 lg:hidden"
         style="background-color: rgba(15, 23, 42, 0.5);">
    </div>
</nav>
