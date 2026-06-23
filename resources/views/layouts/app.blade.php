<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
    <body class="font-sans antialiased text-slate-800 bg-[#F8F9FA] bg-grid-pattern h-screen overflow-hidden">
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
                <main class="flex-grow pb-12">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
