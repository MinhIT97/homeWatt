<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mất kết nối - HomeWatt</title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/favicon.png">
        
        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#3B82F6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=outfit:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles (Inline static styles to ensure offline works even if stylesheet fails) -->
        <style>
            :root {
                --blue-500: #3b82f6;
                --slate-900: #0f172a;
                --slate-950: #020617;
                --slate-800: #1e293b;
                --slate-400: #94a3b8;
                --slate-200: #e2e8f0;
            }

            body {
                font-family: 'Figtree', 'Outfit', sans-serif;
                background-color: var(--slate-950);
                color: var(--slate-200);
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                overflow: hidden;
            }

            /* Background grid pattern */
            .bg-grid {
                position: absolute;
                inset: 0;
                background-size: 40px 40px;
                background-image: 
                    linear-gradient(to right, rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
                z-index: 1;
            }

            /* Floating glow circles */
            .glow-1 {
                position: absolute;
                top: 25%;
                left: 25%;
                width: 300px;
                height: 300px;
                background: rgba(59, 130, 246, 0.1);
                border-radius: 50%;
                filter: blur(100px);
                z-index: 2;
                animation: float 6s ease-in-out infinite;
            }

            .glow-2 {
                position: absolute;
                bottom: 25%;
                right: 25%;
                width: 350px;
                height: 350px;
                background: rgba(6, 182, 212, 0.08);
                border-radius: 50%;
                filter: blur(120px);
                z-index: 2;
                animation: float 6s ease-in-out infinite;
                animation-delay: 3s;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-15px); }
            }

            /* Card container */
            .card {
                position: relative;
                z-index: 10;
                width: 90%;
                max-width: 440px;
                padding: 2.5rem;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 1.5rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                text-align: center;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 2rem;
                text-decoration: none;
            }

            .brand-icon {
                padding: 0.5rem;
                background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
                border-radius: 0.75rem;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            }

            .brand-name {
                font-family: 'Outfit', sans-serif;
                font-size: 1.5rem;
                font-weight: 700;
                color: white;
            }

            .offline-icon-wrapper {
                position: relative;
                width: 80px;
                height: 80px;
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
            }

            .offline-icon-wrapper::after {
                content: '';
                position: absolute;
                inset: -6px;
                border: 2px dashed rgba(239, 68, 68, 0.3);
                border-radius: 50%;
                animation: spin 15s linear infinite;
            }

            @keyframes spin {
                100% { transform: rotate(360deg); }
            }

            h1 {
                font-family: 'Outfit', sans-serif;
                font-size: 1.5rem;
                color: white;
                margin: 0 0 0.75rem 0;
            }

            p {
                color: var(--slate-400);
                font-size: 0.95rem;
                line-height: 1.5;
                margin: 0 0 2rem 0;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn {
                font-family: 'Figtree', sans-serif;
                font-size: 0.9rem;
                font-weight: 600;
                padding: 0.875rem;
                border-radius: 0.75rem;
                cursor: pointer;
                transition: all 0.2s ease-in-out;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                text-decoration: none;
            }

            .btn-primary {
                background: #3b82f6;
                color: white;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            }

            .btn-primary:hover {
                background: #2563eb;
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.05);
                color: var(--slate-200);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }

            .btn-secondary:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body>
        <div class="bg-grid"></div>
        <div class="glow-1"></div>
        <div class="glow-2"></div>
        
        <div class="card">
            <!-- Brand Logo -->
            <a href="/" class="brand">
                <span class="brand-icon">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </span>
                <span class="brand-name">HomeWatt</span>
            </a>

            <!-- Offline Icon -->
            <div class="offline-icon-wrapper">
                <svg style="width: 36px; height: 36px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.268 7.268a9 9 0 0110.464 0M7.43 10.103a5 5 0 018.14 0M12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"></path>
                </svg>
            </div>

            <!-- Content -->
            <h1>{{ __('Mất kết nối Internet') }}</h1>
            <p>{{ __('HomeWatt hiện đang chạy ngoại tuyến. Vui lòng kiểm tra lại kết nối mạng của bạn để cập nhật và đồng bộ dữ liệu mới nhất.') }}</p>

            <!-- Buttons -->
            <div class="btn-group">
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"></path>
                    </svg>
                    {{ __('Tải lại trang') }}
                </button>
                <a href="/dashboard" class="btn btn-secondary">
                    {{ __('Quay lại bảng điều khiển') }}
                </a>
            </div>
        </div>
    </body>
</html>
