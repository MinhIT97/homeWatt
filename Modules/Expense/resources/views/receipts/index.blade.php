<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight font-outfit">
                    Thư viện hóa đơn
                </h2>
                <p class="text-xs text-slate-500 mt-1">Xem lại các hóa đơn đã chụp và quét qua Telegram</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Từ ngày</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="bg-slate-50 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Đến ngày</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="bg-slate-50 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Danh mục</label>
                        <select name="category_id" class="bg-slate-50 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            <option value="">Tất cả</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-500 transition shadow-sm">Lọc</button>
                        <a href="{{ route('receipts.index') }}" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 transition">Xóa lọc</a>
                    </div>
                </form>
            </div>

            @if($receipts->isEmpty())
                <div class="text-center py-16">
                    <span class="text-5xl mb-4 block">🧾</span>
                    <h3 class="text-lg font-bold text-slate-700 mb-2">Chưa có hóa đơn nào</h3>
                    <p class="text-sm text-slate-500">Các hóa đơn được chụp và quét qua Telegram sẽ xuất hiện ở đây.</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($receipts as $receipt)
                        @php
                            $media = $receipt->media;
                        @endphp
                        <a href="{{ $media?->url() }}" target="_blank" class="group bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-1 transition duration-200 block">
                            <!-- Thumbnail -->
                            <div class="aspect-[4/3] bg-slate-100 overflow-hidden relative">
                                @if($media && $media->isImage())
                                    <img src="{{ $media->url() }}" alt="Receipt" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                @endif

                                <!-- Hover Overlay -->
                                <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/40 transition flex items-end p-2 opacity-0 group-hover:opacity-100">
                                    <div class="text-white text-[10px] font-bold">
                                        <p>{{ $receipt->occurred_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="p-3">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $receipt->description ?: 'Hóa đơn #' . $receipt->id }}</p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-sm font-extrabold text-slate-900">{{ number_format($receipt->amount, 0, ',', '.') }} đ</span>
                                    @if($receipt->category)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-500">{{ $receipt->category->name }}</span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">{{ $receipt->occurred_at->format('d/m/Y') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $receipts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
