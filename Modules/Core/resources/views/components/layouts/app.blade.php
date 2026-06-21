<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'HomeWatt'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-gray-50 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        <!-- Top nav -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden -ml-2 mr-2 p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <a href="{{ route('home') }}" class="text-xl font-bold text-primary-600">HomeWatt</a>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <span class="text-sm text-gray-600 hidden sm:block">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:text-primary-800">Login</a>
                            <a href="{{ route('register') }}" class="text-sm text-primary-600 hover:text-primary-800">Register</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar -->
            @auth
            <aside class="hidden lg:block w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] sticky top-16" :class="{ 'hidden': !sidebarOpen }">
                <nav class="p-4 space-y-1">
                    <x-core::nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-core::nav-link>
                </nav>
            </aside>
            @endauth

            <!-- Main content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
