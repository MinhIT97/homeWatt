<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">AI Nhận Diện Thiết Bị</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="flex justify-end mb-6">
                <a href="{{ route('ai.analyses.create') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-500/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform w-full sm:w-auto text-center">
                    + Phân tích ảnh mới
                </a>
            </div>

            @if($analyses->isEmpty())
                <div class="glass-panel rounded-3xl border border-slate-200/60 shadow-sm p-12 text-center max-w-md mx-auto">
                    <div class="text-6xl mb-6">🤖</div>
                    <h3 class="text-xl font-bold text-slate-800 font-outfit mb-2">Chưa có phân tích nào</h3>
                    <p class="text-slate-500 text-sm mb-6">Tải ảnh tem nhãn thiết bị lên để AI trích xuất thông số kỹ thuật tự động.</p>
                    <a href="{{ route('ai.analyses.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-accent-500 hover:from-primary-500 hover:to-accent-400 text-white text-sm font-semibold rounded-xl shadow-md shadow-primary-500/15 hover:shadow-lg transition duration-150 hover:-translate-y-0.5 transform">
                        Bắt đầu phân tích
                    </a>
                </div>
            @else
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                    <!-- Mobile Card List View -->
                    <div class="block sm:hidden divide-y divide-slate-100">
                        @foreach($analyses as $analysis)
                            <a href="{{ route('ai.analyses.show', $analysis) }}" class="block p-4 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <span class="text-sm font-bold text-slate-800 font-outfit">Phân tích #{{ $analysis->media_id }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize shrink-0
                                        @if($analysis->status === 'completed') bg-green-50 text-green-700 border-green-200
                                        @elseif($analysis->status === 'failed') bg-red-50 text-red-700 border-red-200
                                        @elseif($analysis->status === 'processing') bg-blue-50 text-blue-700 border-blue-200
                                        @else bg-amber-50 text-amber-700 border-amber-200
                                        @endif">
                                        {{ $analysis->status }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500 mt-2">
                                    <span class="font-medium text-slate-650">
                                        Độ tin cậy: <span class="font-semibold text-slate-800">{{ $analysis->result ? round($analysis->result->confidence * 100) . '%' : '—' }}</span>
                                    </span>
                                    <span class="font-semibold text-slate-450">${{ number_format($analysis->result?->cost ?? 0, 6) }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    {{ $analysis->created_at->format('Y-m-d H:i') }}
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ảnh</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng thái</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Độ tin cậy</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Chi phí</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($analyses as $analysis)
                                    <tr class="hover:bg-slate-50/50 transition cursor-pointer" onclick="location.href='{{ route('ai.analyses.show', $analysis) }}'">
                                        <td class="px-6 py-4 text-sm text-slate-500">#{{ $analysis->media_id }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border capitalize
                                                @if($analysis->status === 'completed') bg-green-50 text-green-700 border-green-200
                                                @elseif($analysis->status === 'failed') bg-red-50 text-red-700 border-red-200
                                                @elseif($analysis->status === 'processing') bg-blue-50 text-blue-700 border-blue-200
                                                @else bg-amber-50 text-amber-700 border-amber-200
                                                @endif">
                                                {{ $analysis->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $analysis->result ? round($analysis->result->confidence * 100) . '%' : '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">${{ number_format($analysis->result?->cost ?? 0, 6) }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500">{{ $analysis->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">{{ $analyses->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
