<div class="w-64 bg-white border-r border-slate-200/60 flex flex-col h-screen sticky top-0 shrink-0 select-none">
    <!-- Logo Section -->
    <div class="h-16 flex items-center px-6 border-b border-slate-100">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="p-1.5 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg text-white shadow-md shadow-blue-500/20 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </span>
            <span class="text-xl font-bold tracking-tight text-slate-800 font-outfit">HomeWatt</span>
        </a>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 py-6 px-4 space-y-1.5 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Tổng quan</span>
            </div>
        </a>

        <!-- Homes -->
        <a href="{{ route('homes.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('homes.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('homes.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span>Ngôi nhà</span>
            </div>
        </a>

        <!-- Devices -->
        <a href="{{ route('devices.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('devices.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('devices.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
                <span>Thiết bị</span>
            </div>
        </a>

        <!-- Rooms -->
        <a href="{{ route('rooms.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('rooms.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('rooms.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span>Phòng</span>
            </div>
        </a>

        <!-- AI Analysis -->
        <a href="{{ route('ai.analyses.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('ai.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('ai.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <span>AI Nhận diện</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-600 rounded-full">Mới</span>
        </a>

        <!-- Energy (Thống kê) -->
        <a href="{{ route('energy.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('energy.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('energy.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Thống kê</span>
            </div>
        </a>

        <!-- Tariff (Hóa đơn điện) -->
        <a href="{{ route('tariff.index') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('tariff.*') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('tariff.*') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Hóa đơn điện</span>
            </div>
        </a>

        <!-- Settings (Cài đặt) -->
        <a href="{{ route('profile.edit') }}" class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('profile.edit') ? 'bg-blue-50/50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 {{ request()->routeIs('profile.edit') ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Cài đặt</span>
            </div>
        </a>
    </div>

    <!-- Tip Card and Footer -->
    <div class="p-4 border-t border-slate-100 space-y-4">
        <!-- Tip Card -->
        <div class="bg-blue-50/75 border border-blue-100 rounded-2xl p-4 text-center relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-12 h-12 bg-blue-100/40 rounded-full blur-sm"></div>
            <div class="w-10 h-10 bg-blue-600/10 text-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h5 class="text-xs font-bold text-slate-800 font-outfit mb-1">Mẹo tiết kiệm điện</h5>
            <p class="text-[11px] text-slate-500 leading-relaxed mb-3">Tắt thiết bị không sử dụng giúp tiết kiệm 10-15% tiền điện.</p>
            <a href="{{ route('ai.analyses.index') }}" class="inline-block w-full py-1.5 bg-white border border-blue-200 text-blue-600 text-xs font-bold rounded-lg hover:bg-blue-50 transition">Xem thêm</a>
        </div>

        <!-- Copyright -->
        <p class="text-[10px] text-slate-400 text-center font-medium">
            &copy; 2025 HomeWatt. Bảo lưu mọi quyền.
        </p>
    </div>
</div>
