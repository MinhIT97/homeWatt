<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-init="$store.theme.init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HomeWatt') }} - {{ __('navigation.dashboard') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- PWA -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#3B82F6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="HomeWatt">
        <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }

            // Lưu trữ sự kiện trước khi cài đặt để hiển thị nút cài đặt thủ công
            window.deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                window.deferredPrompt = e;
                // Phát tín hiệu cho Alpine.js biết app có thể cài đặt được
                window.dispatchEvent(new CustomEvent('pwa-install-ready'));
            });
            window.addEventListener('appinstalled', () => {
                window.deferredPrompt = null;
                window.dispatchEvent(new CustomEvent('pwa-installed-success'));
            });
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 dark:text-slate-200 bg-[#F8F9FA] dark:bg-slate-950 bg-grid-pattern h-screen overflow-hidden">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar Navigation -->
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
                <!-- Top Header Bar -->
                @include('layouts.navigation')

                <!-- Page Heading -->
                @isset($header)
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 w-full shrink-0">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Page Content -->
                <main class="flex-grow pb-24 lg:pb-12">
                    {{ $slot }}
                    <!-- Mobile Bottom Navigation Bar -->
        <div class="fixed bottom-4 left-4 right-4 z-40 lg:hidden bg-white/85 dark:bg-slate-900/85 backdrop-blur-xl border border-white/40 dark:border-slate-700/40 rounded-3xl shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50 px-2 py-1.5 select-none transition-all duration-200">
            <div class="flex justify-around items-center h-14">
                <!-- Overview / Tổng quan -->
                <a href="{{ route('dashboard') }}" class="relative flex flex-col items-center justify-center w-14 py-1 transition duration-150 {{ request()->routeIs('dashboard') ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-650' }}">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-[9px] tracking-tight">Tổng quan</span>
                    @if(request()->routeIs('dashboard'))
                        <span class="absolute bottom-0 w-1 h-1 bg-blue-600 rounded-full"></span>
                    @endif
                </a>

                <!-- Wallets / Tài khoản -->
                <a href="{{ route('wallets.index') }}" class="relative flex flex-col items-center justify-center w-14 py-1 transition duration-150 {{ request()->routeIs('wallets.*') ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-650' }}">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="text-[9px] tracking-tight">Tài khoản</span>
                    @if(request()->routeIs('wallets.*'))
                        <span class="absolute bottom-0 w-1 h-1 bg-blue-600 rounded-full"></span>
                    @endif
                </a>

                <!-- Plus (+) / Ghi chép -->
                @if(request()->routeIs('dashboard') || request()->routeIs('expenses.index'))
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-quick-entry'))" class="flex flex-col items-center justify-center -translate-y-4 z-50">
                        <div class="rounded-full bg-gradient-to-tr from-blue-500 to-cyan-400 flex items-center justify-center text-white shadow-md shadow-blue-500/20 border-4 border-white active:scale-95 transition duration-150" style="width: 48px; height: 48px;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-[9px] text-blue-600 font-bold tracking-tight mt-0.5">Ghi chép</span>
                    </button>
                @else
                    <a href="{{ route('expenses.create') }}" class="flex flex-col items-center justify-center -translate-y-4 z-50">
                        <div class="rounded-full bg-gradient-to-tr from-blue-500 to-cyan-400 flex items-center justify-center text-white shadow-md shadow-blue-500/20 border-4 border-white active:scale-95 transition duration-150" style="width: 48px; height: 48px;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-[9px] text-blue-600 font-bold tracking-tight mt-0.5">Ghi chép</span>
                    </a>
                @endif

                <!-- Reports / Báo cáo -->
                <a href="{{ route('reports.monthly') }}" class="relative flex flex-col items-center justify-center w-14 py-1 transition duration-150 {{ request()->routeIs('reports.*') || request()->routeIs('reports.monthly') ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-650' }}">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-[9px] tracking-tight">Báo cáo</span>
                    @if(request()->routeIs('reports.*') || request()->routeIs('reports.monthly'))
                        <span class="absolute bottom-0 w-1 h-1 bg-blue-600 rounded-full"></span>
                    @endif
                </a>

                <!-- Settings / Khác -->
                <a href="{{ route('profile.edit') }}" class="relative flex flex-col items-center justify-center w-14 py-1 transition duration-150 {{ request()->routeIs('profile.edit') ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-650' }}">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span class="text-[9px] tracking-tight">Khác</span>
                    @if(request()->routeIs('profile.edit'))
                        <span class="absolute bottom-0 w-1 h-1 bg-blue-600 rounded-full"></span>
                    @endif
                </a>
            </div>
        </div>
                </main>
            </div>
        </div>
    </body>
</html>
