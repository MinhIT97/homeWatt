<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Đăng nhập hoặc đăng ký tài khoản HomeWatt để bắt đầu theo dõi thiết bị, điện năng và tính hóa đơn điện bằng AI.">

        <title>{{ config('app.name', 'HomeWatt') }} - Xác thực hệ thống</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-100 antialiased bg-slate-950 min-h-screen relative overflow-x-hidden bg-grid-pattern">
        <!-- Floating glow circles in background -->
        <div class="absolute top-1/4 left-1/4 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary-600/20 rounded-full blur-[100px] pointer-events-none animate-float"></div>
        <div class="absolute bottom-1/4 right-1/4 translate-x-1/2 translate-y-1/2 w-[400px] h-[400px] bg-accent-500/10 rounded-full blur-[120px] pointer-events-none animate-float-delayed"></div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10">
            <div class="mb-4">
                <a href="/" class="text-3xl font-extrabold tracking-tight flex items-center gap-2">
                    <span class="p-2 bg-gradient-to-br from-primary-500 to-accent-400 rounded-xl shadow-lg shadow-primary-500/20 text-white">⚡</span>
                    <span class="text-gradient-purple-cyan font-outfit">HomeWatt</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-8 py-8 dark-glass-panel shadow-2xl overflow-hidden sm:rounded-2xl border border-slate-800/80">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
