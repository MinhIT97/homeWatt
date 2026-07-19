<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-slate-900 font-outfit">
            {{ __('Liên kết Telegram Bot') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __('Ghi chép giao dịch (thu nhập, chi tiêu, cho vay, đi vay) thông minh cực nhanh qua tin nhắn chat Telegram.') }}
        </p>
    </header>

    @if (session('status') === 'telegram-unlinked')
        <div class="p-4 bg-yellow-50 border border-yellow-250 text-yellow-800 rounded-xl text-sm font-medium">
            {{ __('Đã hủy liên kết Telegram thành công.') }}
        </div>
    @endif

    @if ($user->telegram_chat_id)
        <div class="p-5 bg-green-50 border border-green-200 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl p-2 bg-white rounded-xl shadow-sm">🤖</span>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">Đã kết nối thành công</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Chat ID: <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700">{{ $user->telegram_chat_id }}</span></p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.telegram.unlink') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-650 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                    {{ __('Hủy kết nối') }}
                </button>
            </form>
        </div>
    @else
        @php
            $code = $user->telegram_verification_code;
            $botUsername = config('services.telegram.bot_username', 'homewatt_bot');
        @endphp

        @if ($code)
            <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50/50 border border-blue-150 rounded-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl p-2 bg-white rounded-xl shadow-sm">🔑</span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Mã liên kết của bạn</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Mã này có hiệu lực trong phiên làm việc hiện tại.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 py-2">
                    <span class="text-3xl font-extrabold tracking-widest text-primary-700 font-mono bg-white border border-slate-200 px-6 py-2.5 rounded-xl shadow-sm">
                        {{ $code }}
                    </span>
                    <a href="https://t.me/{{ $botUsername }}?start={{ $code }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Mở Telegram Bot
                    </a>
                </div>

                <div class="text-xs text-slate-550 space-y-2 border-t border-slate-100 pt-3">
                    <p class="font-semibold text-slate-700">Hướng dẫn kích hoạt:</p>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Nhấn nút <strong>Mở Telegram Bot</strong> phía trên để mở cuộc trò chuyện.</li>
                        <li>Nhấn <strong>Start</strong> hoặc gửi tin nhắn chứa mã liên kết: <code class="bg-white border px-1 rounded font-mono">/start {{ $code }}</code></li>
                        <li>Hệ thống sẽ xác nhận liên kết thành công ngay lập tức!</li>
                    </ol>
                </div>

                <div class="flex justify-end pt-2">
                    <form method="POST" action="{{ route('profile.telegram.code') }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 underline font-semibold">
                            {{ __('Đổi mã khác') }}
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="flex justify-between items-center bg-slate-50 border border-slate-200/60 p-5 rounded-2xl">
                <div class="flex items-center gap-3">
                    <span class="text-2xl p-2 bg-white rounded-xl shadow-sm">🔌</span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Chưa liên kết Telegram</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Tạo mã để bắt đầu đồng bộ hóa dữ liệu.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('profile.telegram.code') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        {{ __('Lấy mã liên kết') }}
                    </button>
                </form>
            </div>
        @endif
    @endif
    <!-- Cú pháp ghi chép thông minh (Syntax Guide) -->
    <div class="mt-6 border-t border-slate-100 pt-6">
        <h3 class="text-sm font-bold text-slate-800 font-outfit flex items-center gap-2 mb-3">
            <span>💡</span> Hướng dẫn cú pháp ghi chép nhanh
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <p class="font-bold text-slate-700">1. Quy tắc viết số tiền:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-600">
                    <li>Dùng <code class="bg-white border px-1 rounded font-mono text-slate-800">k</code> cho nghìn: <code class="bg-white border px-1 rounded font-mono text-slate-800">50k</code> &rarr; 50.000đ</li>
                    <li>Dùng <code class="bg-white border px-1 rounded font-mono text-slate-800">m</code>, <code class="bg-white border px-1 rounded font-mono text-slate-800">tr</code> cho triệu: <code class="bg-white border px-1 rounded font-mono text-slate-800">1.5m</code> hoặc <code class="bg-white border px-1 rounded font-mono text-slate-800">2tr</code> &rarr; 1.500.000đ / 2.000.000đ</li>
                    <li>Nhập số trực tiếp: <code class="bg-white border px-1 rounded font-mono text-slate-800">500000</code> hoặc <code class="bg-white border px-1 rounded font-mono text-slate-800">500.000</code></li>
                    <li>Viết tắt nhanh: <code class="bg-white border px-1 rounded font-mono text-slate-800">50</code> tự hiểu là <code class="bg-white border px-1 rounded font-mono text-slate-800">50k</code> (50.000đ)</li>
                </ul>
            </div>

            <div class="space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <p class="font-bold text-slate-700">2. Các nhóm giao dịch:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-600">
                    <li><strong class="text-red-650">Chi tiền:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">chi 50k ăn trưa</code> hoặc <code class="bg-white border px-1 rounded font-mono text-slate-800">mua 150k quần áo</code></li>
                    <li><strong class="text-green-650">Thu tiền:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">thu 10m lương</code> hoặc <code class="bg-white border px-1 rounded font-mono text-slate-800">nhận 200k quà sinh nhật</code></li>
                    <li><strong class="text-purple-650">Cho vay:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">cho vay 1tr nam</code></li>
                    <li><strong class="text-purple-650">Trả nợ:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">trả nợ 500k bạn bè</code></li>
                    <li><strong class="text-blue-650">Đi vay:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">đi vay 5tr ngân hàng</code></li>
                    <li><strong class="text-blue-650">Thu nợ:</strong> <code class="bg-white border px-1 rounded font-mono text-slate-800">thu nợ 200k</code></li>
                </ul>
            </div>
        </div>

        <div class="mt-4 space-y-3 bg-blue-50/50 border border-blue-100 rounded-xl p-4 text-xs text-slate-600 leading-relaxed">
            <div>
                <span class="font-bold text-blue-700">📌 Tự động nhận diện danh mục:</span>
                Dựa trên từ khóa trong mô tả của bạn (ví dụ: <em>phở, cafe, xăng, grab, điện, nước, shopee, thuốc, gym...</em>), bot sẽ tự phân loại giao dịch vào hạng mục phù hợp (Ăn uống, Đi lại, Nhà cửa, Mua sắm, Sức khỏe...) một cách thông minh.
            </div>
            <div class="border-t border-blue-200/50 pt-2.5">
                <span class="font-bold text-blue-700">💳 Tự động nhận diện Ví nguồn:</span>
                Nhập tên ví hoặc từ viết tắt trong tin nhắn của bạn (ví dụ: <code class="bg-white border px-1 rounded font-mono text-slate-800">momo</code>, <code class="bg-white border px-1 rounded font-mono text-slate-800">tech</code>, <code class="bg-white border px-1 rounded font-mono text-slate-800">vcb</code>, <code class="bg-white border px-1 rounded font-mono text-slate-800">tiền mặt</code>...) để hệ thống ghi vào ví đó và lược bỏ tên ví khỏi ghi chú. Nếu không điền, mặc định ghi nhận vào ví <strong>Tiền mặt</strong> của bạn.
            </div>
        </div>
    </div>
</section>
