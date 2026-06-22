<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>HomeWatt - Quản Lý Điện Năng Gia Đình & Trích Xuất Thông Số Bằng AI Vision</title>
    <meta name="description" content="HomeWatt là ứng dụng quản lý điện năng hộ gia đình đột phá. Sử dụng AI Vision trích xuất thông số tem nhãn tự động, tính tiền điện thực tế theo biểu giá lũy tiến EVN và tối ưu hóa hóa đơn điện mỗi tháng.">
    <meta name="keywords" content="HomeWatt, tiết kiệm điện, quản lý điện năng gia đình, AI Vision, đọc thông số tem nhãn, biểu giá điện EVN, dự đoán tiền điện, hóa đơn điện năng">
    <meta name="author" content="HomeWatt Team">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook / Zalo SEO -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="HomeWatt - Quản Lý Điện Năng Gia Đình & Trích Xuất Thông Số Bằng AI Vision">
    <meta property="og:description" content="Chụp ảnh tem nhãn thiết bị để AI tự động lập hồ sơ năng lượng gia đình bạn, tính toán chi phí chính xác theo biểu giá hiện tại và dự kiến hóa đơn.">
    <meta property="og:image" content="{{ asset('images/appliance_spec_sticker.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="HomeWatt">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-100 bg-slate-950 min-h-screen relative overflow-x-hidden bg-grid-pattern antialiased">
    <!-- Floating background glow decoration -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-600/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute top-[800px] left-[-200px] w-[600px] h-[600px] bg-accent-500/5 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="absolute bottom-[400px] right-[-100px] w-[500px] h-[500px] bg-primary-500/5 rounded-full blur-[130px] pointer-events-none"></div>

    <!-- Header Navigation -->
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/70 border-b border-slate-900/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-8">
                    <!-- Logo -->
                    <a href="#" class="text-2xl font-extrabold tracking-tight flex items-center gap-2">
                        <span class="p-2 bg-gradient-to-br from-primary-500 to-accent-400 rounded-xl shadow-lg shadow-primary-500/20 text-white animate-pulse">⚡</span>
                        <span class="text-gradient-purple-cyan font-outfit">HomeWatt</span>
                    </a>
                    
                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="#features" class="text-sm text-slate-400 hover:text-white transition font-medium">Tính Năng</a>
                        <a href="#ai-vision" class="text-sm text-slate-400 hover:text-white transition font-medium">AI Vision</a>
                        <a href="#calculator" class="text-sm text-slate-400 hover:text-white transition font-medium">Tính Tiền Điện</a>
                        <a href="#about" class="text-sm text-slate-400 hover:text-white transition font-medium">Về Chúng Tôi</a>
                    </nav>
                </div>

                <!-- CTA Actions (Desktop) -->
                <div class="hidden md:flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 text-white rounded-xl shadow-lg shadow-primary-600/10 text-sm font-semibold transition hover:-translate-y-0.5">Vào Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-slate-300 hover:text-white transition font-medium px-4 py-2">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 bg-white text-slate-950 hover:bg-slate-100 rounded-xl text-sm font-bold transition hover:-translate-y-0.5 shadow-lg shadow-white/5">Đăng ký miễn phí</a>
                    @endauth
                </div>

                <!-- Hamburger Button (Mobile) -->
                <div class="flex md:hidden items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-900 focus:outline-none transition">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (AlpineJS) -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="md:hidden border-t border-slate-900 bg-slate-950/95 backdrop-blur-md py-4 px-6 space-y-4 shadow-xl"
             style="display: none;">
            <nav class="flex flex-col gap-3">
                <a href="#features" @click="mobileMenuOpen = false" class="text-sm text-slate-400 hover:text-white transition font-medium py-1">Tính Năng</a>
                <a href="#ai-vision" @click="mobileMenuOpen = false" class="text-sm text-slate-400 hover:text-white transition font-medium py-1">AI Vision</a>
                <a href="#calculator" @click="mobileMenuOpen = false" class="text-sm text-slate-400 hover:text-white transition font-medium py-1">Tính Tiền Điện</a>
                <a href="#about" @click="mobileMenuOpen = false" class="text-sm text-slate-400 hover:text-white transition font-medium py-1">Về Chúng Tôi</a>
            </nav>
            <div class="pt-4 border-t border-slate-900 flex flex-col gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full text-center py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-semibold shadow-md">Vào Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="w-full text-center py-2.5 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white rounded-xl text-sm font-medium">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 bg-white text-slate-950 rounded-xl text-sm font-bold shadow-md">Đăng ký miễn phí</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 md:pt-24 md:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                <!-- Hero Content -->
                <div class="lg:col-span-7 space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wider text-accent-400 uppercase">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-500"></span>
                        </span>
                        Công nghệ AI Vision Mới Nhất
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                        Kiểm soát hóa đơn điện<br class="hidden sm:inline">
                        <span class="text-gradient-purple-cyan font-outfit">Thông minh bằng AI</span>
                    </h1>
                    
                    <p class="text-lg text-slate-400 max-w-2xl mx-auto lg:mx-0">
                        Chụp ảnh nhãn thiết bị để tự động trích xuất thông số kỹ thuật. HomeWatt tính toán điện năng, quy đổi chi phí theo biểu giá EVN bậc thang và đề xuất phương án tiết kiệm hiệu quả nhất.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ route('register') }}" class="w-full sm:w-auto text-center px-8 py-4 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white font-bold rounded-xl shadow-lg shadow-primary-500/20 transition hover:-translate-y-0.5">
                            Bắt đầu ngay miễn phí
                        </a>
                        <a href="#ai-vision" class="w-full sm:w-auto text-center px-8 py-4 bg-slate-900/60 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800/80 font-bold rounded-xl transition hover:-translate-y-0.5">
                            Xem Demo hoạt động
                        </a>
                    </div>

                    <!-- Statistics grid -->
                    <div class="grid grid-cols-3 gap-4 pt-8 border-t border-slate-900 max-w-lg mx-auto lg:mx-0">
                        <div>
                            <p class="text-3xl font-extrabold text-white">98.5%</p>
                            <p class="text-xs text-slate-500 mt-1 uppercase font-semibold">AI chính xác</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-white">100%</p>
                            <p class="text-xs text-slate-500 mt-1 uppercase font-semibold">Theo giá EVN</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-white">0 VND</p>
                            <p class="text-xs text-slate-500 mt-1 uppercase font-semibold">Trải nghiệm miễn phí</p>
                        </div>
                    </div>
                </div>

                <!-- Hero Graphic (Mockup App) -->
                <div class="lg:col-span-5 relative flex justify-center">
                    <div class="relative w-full max-w-[420px] aspect-[9/16] rounded-[40px] border-[10px] border-slate-900 bg-slate-950 overflow-hidden shadow-2xl glow-primary animate-float">
                        <!-- Simulated App UI Header -->
                        <div class="h-14 border-b border-slate-900 px-6 flex justify-between items-center bg-slate-950/80 backdrop-blur">
                            <span class="text-sm font-bold text-slate-200">⚡ HomeWatt App</span>
                            <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                        </div>
                        
                        <!-- Simulated App Content -->
                        <div class="p-6 space-y-5">
                            <div class="space-y-1">
                                <p class="text-xs text-slate-500">Ước tính tháng này</p>
                                <p class="text-3xl font-extrabold text-white">1,248,500 <span class="text-sm font-medium text-slate-400">VND</span></p>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-2 bg-slate-900/50 border border-slate-900 p-4 rounded-2xl">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-400">Tỷ lệ đo thực tế (Data Quality)</span>
                                    <span class="text-green-400 font-bold">75%</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden">
                                    <div class="bg-gradient-to-r from-primary-500 to-green-400 h-full rounded-full" style="width: 75%"></div>
                                </div>
                            </div>

                            <!-- Device usage list -->
                            <div class="space-y-3">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Thiết bị tiêu thụ nhiều</p>
                                
                                <div class="flex justify-between items-center p-3 bg-slate-900/30 border border-slate-900/50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <span class="p-2 bg-purple-500/10 rounded-lg text-purple-400 text-sm">❄️</span>
                                        <div>
                                            <p class="text-sm font-semibold">Điều hòa phòng khách</p>
                                            <p class="text-xs text-slate-500">1200W • 8h/ngày</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-200">620,000đ</p>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-slate-900/30 border border-slate-900/50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <span class="p-2 bg-blue-500/10 rounded-lg text-blue-400 text-sm">🔌</span>
                                        <div>
                                            <p class="text-sm font-semibold">Bình nước nóng</p>
                                            <p class="text-xs text-slate-500">2500W • 1.5h/ngày</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-200">285,000đ</p>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-slate-900/30 border border-slate-900/50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <span class="p-2 bg-yellow-500/10 rounded-lg text-yellow-400 text-sm">🥬</span>
                                        <div>
                                            <p class="text-sm font-semibold">Tủ lạnh Inverter</p>
                                            <p class="text-xs text-slate-500">150W • 24h/ngày</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-200">190,000đ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Grid Section -->
    <section id="features" class="py-20 bg-slate-950 border-t border-slate-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-white">Tính Năng Ưu Việt Của HomeWatt</h2>
                <p class="text-slate-400">Thiết kế đáp ứng đầy đủ yêu cầu khắt khe về bảo mật dữ liệu riêng tư, độ tin cậy của thuật toán tính toán và ứng dụng AI thực tiễn.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="p-6 bg-slate-900/40 border border-slate-900 hover:border-slate-800 rounded-2xl transition duration-300 group hover:-translate-y-1">
                    <div class="w-12 h-12 bg-primary-600/10 rounded-xl flex items-center justify-center text-primary-400 text-xl font-bold mb-6 group-hover:scale-110 transition duration-300">📸</div>
                    <h3 class="text-xl font-bold text-white mb-3">AI Vision Scanner</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Chụp ảnh tem kỹ thuật, nhãn năng lượng. AI tự động nhận diện công suất định mức, dòng điện, hãng, model một cách chuẩn xác.</p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 bg-slate-900/40 border border-slate-900 hover:border-slate-800 rounded-2xl transition duration-300 group hover:-translate-y-1">
                    <div class="w-12 h-12 bg-accent-600/10 rounded-xl flex items-center justify-center text-accent-400 text-xl font-bold mb-6 group-hover:scale-110 transition duration-300">📊</div>
                    <h3 class="text-xl font-bold text-white mb-3">Biểu Giá EVN Đa Dạng</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Không hardcode giá điện. Hệ thống lưu trữ các phiên bản biểu giá điện bậc thang sinh hoạt, kinh doanh, giờ cao điểm linh hoạt.</p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 bg-slate-900/40 border border-slate-900 hover:border-slate-800 rounded-2xl transition duration-300 group hover:-translate-y-1">
                    <div class="w-12 h-12 bg-green-600/10 rounded-xl flex items-center justify-center text-green-400 text-xl font-bold mb-6 group-hover:scale-110 transition duration-300">🔒</div>
                    <h3 class="text-xl font-bold text-white mb-3">Bảo Mật & Riêng Tư</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Hình ảnh và thông số thiết bị là tài sản riêng tư tuyệt đối. Ảnh được lưu trữ bảo mật và phân quyền cấp hộ gia đình chặt chẽ.</p>
                </div>

                <!-- Feature 4 -->
                <div class="p-6 bg-slate-900/40 border border-slate-900 hover:border-slate-800 rounded-2xl transition duration-300 group hover:-translate-y-1">
                    <div class="w-12 h-12 bg-yellow-600/10 rounded-xl flex items-center justify-center text-yellow-400 text-xl font-bold mb-6 group-hover:scale-110 transition duration-300">🔬</div>
                    <h3 class="text-xl font-bold text-white mb-3">Minh Bạch Số Liệu</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Phân định rõ ràng số liệu ước tính chu kỳ và số liệu đo đạc thực tế từ công tơ. Công thức tính toán rõ ràng, dễ đối chiếu.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Vision Scanner Interactive Simulation Section -->
    <section id="ai-vision" class="py-20 bg-slate-950 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Left Info Content -->
                <div class="lg:col-span-5 space-y-6 text-center lg:text-left">
                    <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-white">Quét Tem Nhãn Thiết Bị Bằng AI</h2>
                    <p class="text-slate-400 leading-relaxed">
                        Hệ thống tích hợp mô hình thị giác máy tính cao cấp. Khi bạn tải lên ảnh chụp nhãn thông số của thiết bị (máy lạnh, máy giặt, lò vi sóng...), AI sẽ phân tích và điền sẵn các thông số để bạn duyệt lại.
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex gap-4 items-start text-left">
                            <span class="p-2 bg-slate-900 border border-slate-800 rounded-xl text-primary-400">🛡️</span>
                            <div>
                                <h4 class="font-bold text-white">AI chỉ đề xuất, bạn toàn quyền duyệt</h4>
                                <p class="text-sm text-slate-500">Thông số chỉ được cập nhật chính thức vào thiết bị sau khi bạn kiểm tra và nhấn nút Xác Nhận.</p>
                            </div>
                        </div>

                        <div class="flex gap-4 items-start text-left">
                            <span class="p-2 bg-slate-900 border border-slate-800 rounded-xl text-accent-400">📏</span>
                            <div>
                                <h4 class="font-bold text-white">Nhận diện đơn vị thông minh</h4>
                                <p class="text-sm text-slate-500">Tự động quy đổi W, kW, V, A, kWh/năm về hệ đơn vị tiêu chuẩn để chuẩn hóa việc tính toán công suất.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Scanner Interactive Simulation (Alpine.js supported) -->
                <div class="lg:col-span-7 flex justify-center" x-data="{ scanned: false, isScanning: false, triggerScan() { 
                    this.scanned = false;
                    this.isScanning = true; 
                    setTimeout(() => { 
                        this.isScanning = false; 
                        this.scanned = true; 
                    }, 2500);
                } }">
                    <div class="w-full max-w-2xl bg-slate-900/30 border border-slate-900 p-6 sm:p-8 rounded-3xl grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                        
                        <!-- Col 1: Original Image and Scan Line -->
                        <div class="space-y-3 relative">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Ảnh chụp nhãn kỹ thuật</p>
                            <div class="relative aspect-square rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 flex items-center justify-center">
                                <img src="/images/appliance_spec_sticker.png" alt="Appliance Specification Label" class="w-full h-full object-cover opacity-80">
                                
                                <!-- Scan Line Effect -->
                                <div x-show="isScanning" class="absolute left-0 right-0 h-1 bg-gradient-to-r from-accent-500 to-primary-500 animate-laser glow-accent"></div>
                                
                                <div x-show="!isScanning && !scanned" class="absolute inset-0 bg-slate-950/40 backdrop-blur-[1px] flex items-center justify-center">
                                    <button @click="triggerScan" class="px-5 py-3 bg-primary-600 hover:bg-primary-500 text-white font-bold rounded-xl shadow-lg transition transform hover:scale-105 text-sm">
                                        ⚡ Trích xuất AI
                                    </button>
                                </div>
                            </div>
                            <div class="text-center">
                                <button x-show="scanned || isScanning" @click="triggerScan" :disabled="isScanning" class="text-xs font-medium text-slate-400 hover:text-white transition disabled:opacity-50">
                                    Quét lại ảnh này
                                </button>
                            </div>
                        </div>

                        <!-- Col 2: AI Result Panel -->
                        <div class="flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Kết quả AI phân tích</p>
                                
                                <!-- Empty state -->
                                <div x-show="!isScanning && !scanned" class="h-48 border border-dashed border-slate-800 rounded-2xl flex flex-col items-center justify-center p-4 text-center text-slate-600">
                                    <p class="text-sm">Nhấn nút trích xuất AI bên trái để bắt đầu mô phỏng</p>
                                </div>

                                <!-- Scanning loading state -->
                                <div x-show="isScanning" class="h-48 flex flex-col items-center justify-center space-y-3">
                                    <div class="w-8 h-8 border-4 border-accent-400 border-t-transparent rounded-full animate-spin"></div>
                                    <p class="text-sm text-slate-400 animate-pulse">Đang giải mã nhãn thông số...</p>
                                </div>

                                <!-- Extracted Data list -->
                                <div x-show="scanned && !isScanning" class="space-y-3 transition duration-300">
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">Loại thiết bị</span>
                                            <span class="text-green-400 font-bold">99% Tin cậy</span>
                                        </div>
                                        <div class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm font-semibold text-white">
                                            Điều hòa nhiệt độ
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">Hãng & Model</span>
                                            <span class="text-green-400 font-bold">95% Tin cậy</span>
                                        </div>
                                        <div class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm font-semibold text-white">
                                            HomeWatt AC-2026-X
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-slate-500">Công suất</span>
                                                <span class="text-green-400 font-bold">98%</span>
                                            </div>
                                            <div class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm font-semibold text-white">
                                                1,200 W
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-slate-500">Dòng điện</span>
                                                <span class="text-green-400 font-bold">92%</span>
                                            </div>
                                            <div class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm font-semibold text-white">
                                                5.5 A
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div x-show="scanned && !isScanning" class="mt-6">
                                <a href="{{ route('register') }}" class="block text-center w-full py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 text-white font-bold rounded-xl text-sm shadow-lg shadow-emerald-500/10 transition transform hover:scale-[1.02]">
                                    ✓ Đồng Ý & Thêm Thiết Bị
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Electricity Cost Calculator (EVN Pricing) -->
    <section id="calculator" class="py-20 bg-slate-950 border-t border-slate-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10" x-data="{
            applianceType: 'ac',
            powerWatts: 1200,
            hoursPerDay: 8,
            daysPerMonth: 30,
            
            // Set values based on selected type
            setType(type) {
                this.applianceType = type;
                if (type === 'ac') { this.powerWatts = 1200; this.hoursPerDay = 8; }
                else if (type === 'fridge') { this.powerWatts = 150; this.hoursPerDay = 24; }
                else if (type === 'tv') { this.powerWatts = 100; this.hoursPerDay = 4; }
                else if (type === 'washing') { this.powerWatts = 500; this.hoursPerDay = 1.5; }
            },
            
            // Formula for estimated kWh
            calculateKwh() {
                return ((this.powerWatts * this.hoursPerDay * this.daysPerMonth) / 1000).toFixed(1);
            },
            
            // Calculate EVN pricing tiers 2026 for Vietnamese households
            calculateCost() {
                let kwh = (this.powerWatts * this.hoursPerDay * this.daysPerMonth) / 1000;
                let cost = 0;
                
                // EVN Tariffs:
                let t1 = 1806, t2 = 1866, t3 = 2167, t4 = 2729, t5 = 3050, t6 = 3157;
                
                if (kwh <= 50) {
                    cost = kwh * t1;
                } else if (kwh <= 100) {
                    cost = (50 * t1) + ((kwh - 50) * t2);
                } else if (kwh <= 200) {
                    cost = (50 * t1) + (50 * t2) + ((kwh - 100) * t3);
                } else if (kwh <= 300) {
                    cost = (50 * t1) + (50 * t2) + (100 * t3) + ((kwh - 200) * t4);
                } else if (kwh <= 400) {
                    cost = (50 * t1) + (50 * t2) + (100 * t3) + (100 * t4) + ((kwh - 300) * t5);
                } else {
                    cost = (50 * t1) + (50 * t2) + (100 * t3) + (100 * t4) + (100 * t5) + ((kwh - 400) * t6);
                }
                
                // 8% VAT
                cost = cost * 1.08;
                return Math.round(cost);
            },
            
            // Helper to format number
            formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        }">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Col 1: Calculator Widget -->
                <div class="lg:col-span-7 bg-slate-900/40 border border-slate-900 p-6 sm:p-8 rounded-3xl space-y-6 shadow-2xl">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-800">
                        <h3 class="text-xl font-bold text-white">Công cụ ước tính tiền điện</h3>
                        <span class="px-2.5 py-1 rounded bg-accent-500/10 text-accent-400 text-xs font-semibold">Biểu giá EVN (mới nhất)</span>
                    </div>

                    <!-- Appliance quick buttons -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button @click="setType('ac')" :class="applianceType === 'ac' ? 'bg-primary-600 text-white border-primary-500 shadow-lg shadow-primary-600/10' : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-900/50'" class="py-2.5 px-2 border rounded-xl text-xs font-bold transition flex flex-col items-center gap-1">
                            <span>❄️</span> Điều Hòa
                        </button>
                        <button @click="setType('fridge')" :class="applianceType === 'fridge' ? 'bg-primary-600 text-white border-primary-500 shadow-lg shadow-primary-600/10' : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-900/50'" class="py-2.5 px-2 border rounded-xl text-xs font-bold transition flex flex-col items-center gap-1">
                            <span>🥬</span> Tủ Lạnh
                        </button>
                        <button @click="setType('tv')" :class="applianceType === 'tv' ? 'bg-primary-600 text-white border-primary-500 shadow-lg shadow-primary-600/10' : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-900/50'" class="py-2.5 px-2 border rounded-xl text-xs font-bold transition flex flex-col items-center gap-1">
                            <span>📺</span> TV/Giải Trí
                        </button>
                        <button @click="setType('washing')" :class="applianceType === 'washing' ? 'bg-primary-600 text-white border-primary-500 shadow-lg shadow-primary-600/10' : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-900/50'" class="py-2.5 px-2 border rounded-xl text-xs font-bold transition flex flex-col items-center gap-1">
                            <span>👕</span> Máy Giặt
                        </button>
                    </div>

                    <!-- Sliders -->
                    <div class="space-y-5">
                        <!-- Power Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400 font-medium">Công suất thiết bị</span>
                                <span class="text-white font-bold" x-text="powerWatts + ' Watts'"></span>
                            </div>
                            <input type="range" min="10" max="4000" step="10" x-model="powerWatts" class="w-full h-2 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-accent-500">
                        </div>

                        <!-- Hours Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400 font-medium">Thời gian chạy mỗi ngày</span>
                                <span class="text-white font-bold" x-text="hoursPerDay + ' giờ'"></span>
                            </div>
                            <input type="range" min="0.5" max="24" step="0.5" x-model="hoursPerDay" class="w-full h-2 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-accent-500">
                        </div>

                        <!-- Days Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400 font-medium">Số ngày sử dụng trong tháng</span>
                                <span class="text-white font-bold" x-text="daysPerMonth + ' ngày'"></span>
                            </div>
                            <input type="range" min="1" max="30" step="1" x-model="daysPerMonth" class="w-full h-2 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-accent-500">
                        </div>
                    </div>
                </div>

                <!-- Col 2: Calculation results -->
                <div class="lg:col-span-5 space-y-6 text-center lg:text-left">
                    <div class="space-y-1">
                        <span class="text-xs font-bold text-accent-400 uppercase tracking-widest">Tiêu thụ ước lượng</span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-white">Chi Phí Quy Đổi</h2>
                    </div>

                    <div class="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Điện năng tiêu thụ:</span>
                            <span class="text-xl font-bold text-white" x-text="calculateKwh() + ' kWh'"></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-800/80 pt-4">
                            <span class="text-slate-400">Tiền điện ước tính:</span>
                            <div class="text-right">
                                <p class="text-3xl font-extrabold text-accent-400" x-text="formatNumber(calculateCost()) + ' đ'"></p>
                                <p class="text-[10px] text-slate-500 font-semibold uppercase mt-0.5">*Giá đã bao gồm 8% thuế GTGT</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-slate-400 justify-center lg:justify-start">
                            <span class="text-green-400">✓</span> Tính chuẩn xác theo biểu giá bậc thang EVN
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-400 justify-center lg:justify-start">
                            <span class="text-green-400">✓</span> Hỗ trợ tính thêm VAT
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-400 justify-center lg:justify-start">
                            <span class="text-green-400">✓</span> Công thức minh bạch, lưu lại làm hồ sơ năng lượng
                        </div>
                    </div>

                    <div class="pt-4 flex justify-center lg:justify-start">
                        <a href="{{ route('register') }}" class="px-6 py-3.5 bg-primary-600 hover:bg-primary-500 text-white font-bold rounded-xl text-sm shadow-lg shadow-primary-600/10 transition transform hover:-translate-y-0.5">
                            Bắt đầu tạo hồ sơ của bạn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Banner Section -->
    <section id="about" class="py-20 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="bg-gradient-to-br from-primary-900/60 to-accent-950/60 border border-primary-500/15 p-8 sm:p-12 md:p-16 rounded-[32px] text-center space-y-6 relative overflow-hidden shadow-2xl">
                <!-- Background ambient design -->
                <div class="absolute -top-12 -right-12 w-64 h-64 bg-accent-500/15 rounded-full blur-[60px]"></div>
                
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold font-outfit text-white">Bạn Sẵn Sàng Tiết Kiệm Điện Gia Đình?</h2>
                <p class="text-base text-slate-300 max-w-2xl mx-auto">
                    Đăng ký tài khoản miễn phí ngay hôm nay. Chỉ với vài bức ảnh chụp thiết bị, bạn đã sở hữu hồ sơ năng lượng hoàn chỉnh cho ngôi nhà của mình.
                </p>
                
                <div class="pt-4 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-950 font-bold rounded-xl shadow-lg transition hover:bg-slate-100 hover:-translate-y-0.5">
                        Tạo tài khoản miễn phí
                    </a>
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 bg-slate-950/50 border border-slate-800 text-white font-bold rounded-xl transition hover:bg-slate-900 hover:-translate-y-0.5">
                        Đăng nhập hệ thống
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-gradient-to-br from-primary-500 to-accent-400 rounded-lg text-white text-xs">⚡</span>
                <span class="text-lg font-extrabold font-outfit text-gradient-primary-accent">HomeWatt</span>
            </div>
            
            <p class="text-xs text-slate-500 text-center md:text-right">
                &copy; {{ date('Y') }} HomeWatt. Dự án nghiên cứu tối ưu hóa năng lượng gia đình bằng AI.
            </p>
        </div>
    </footer>
</body>
</html>
