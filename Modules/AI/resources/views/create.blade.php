<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Phân Tích Ảnh Mới</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('ai.analyses.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="media_id" value="Chọn ảnh thiết bị đã tải lên" />
                        <select id="media_id" name="media_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="">Chọn ảnh...</option>
                            @foreach($media as $m)
                                <option value="{{ $m->id }}">Ảnh #{{ $m->id }} — {{ $m->mime_type }} ({{ number_format($m->size / 1024, 1) }} KB)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('media_id')" class="mt-2" />
                        @if($media->isEmpty())
                            <p class="text-xs text-amber-600 mt-2">Bạn cần tải ảnh thiết bị lên trước. Vào trang Thiết bị, chọn thiết bị và tải ảnh tem nhãn.</p>
                        @endif
                    </div>

                    <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                        <p class="font-semibold mb-1">Lưu ý khi phân tích AI:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Ảnh cần rõ nét, chụp trực diện tem thông số kỹ thuật.</li>
                            <li>AI chỉ đề xuất dữ liệu — bạn cần kiểm tra và xác nhận sau khi phân tích.</li>
                            <li>Mỗi lần phân tích sẽ tiêu tốn một lượng nhỏ token AI.</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('ai.analyses.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Bắt đầu phân tích AI</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
