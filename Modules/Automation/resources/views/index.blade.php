<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 dark:text-slate-100 tracking-tight font-outfit">
                    Quy tắc tự động
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tự động hóa công việc dựa trên điều kiện bạn đặt ra</p>
            </div>
            @if($selectedHomeId)
                <a href="{{ route('automation.create', ['home_id' => $selectedHomeId]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                    + Quy tắc mới
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($homes->isNotEmpty())
                <form method="GET" class="mb-6">
                    <select name="home_id" onchange="this.form.submit()"
                            class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 font-semibold">
                        @foreach($homes as $h)
                            <option value="{{ $h->id }}" @selected($selectedHomeId == $h->id)>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if($rules->isEmpty())
                <div class="text-center py-16">
                    <p class="text-4xl mb-3">⚡</p>
                    <p class="text-slate-500 dark:text-slate-400 mb-4">Chưa có quy tắc tự động nào.</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">Gợi ý:</p>
                    <div class="inline-flex flex-col gap-1 text-xs text-slate-500 dark:text-slate-400 text-left">
                        <span>• Khi chi tiêu &gt; 1tr → gắn thẻ "Cần review"</span>
                        <span>• Khi có giao dịch từ Techcombank → tự động đổi danh mục</span>
                        <span>• Khi vượt ngân sách → gửi thông báo Telegram</span>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($rules as $rule)
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5
                                    {{ $rule->is_active ? '' : 'opacity-60' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-bold text-slate-900 dark:text-slate-100">{{ $rule->name }}</h3>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full
                                            {{ $rule->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-700' }}">
                                            {{ $rule->is_active ? 'Đang chạy' : 'Tạm dừng' }}
                                        </span>
                                    </div>
                                    @if($rule->description)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">{{ $rule->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap gap-2 text-[10px]">
                                        <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                                            {{ \Modules\Automation\Services\AutomationEngine::EVENTS[$rule->trigger_event]['label'] ?? $rule->trigger_event }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 rounded-full">
                                            {{ count($rule->conditions) }} điều kiện
                                        </span>
                                        <span class="px-2 py-0.5 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-full">
                                            {{ count($rule->actions) }} hành động
                                        </span>
                                        @if($rule->trigger_count > 0)
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 rounded-full">
                                                Đã chạy {{ $rule->trigger_count }} lần
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <form method="POST" action="{{ route('automation.toggle', $rule) }}">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                                title="{{ $rule->is_active ? 'Tạm dừng' : 'Kích hoạt' }}">
                                            {{ $rule->is_active ? '⏸️' : '▶️' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('automation.edit', $rule) }}" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Sửa">✏️</a>
                                    <a href="{{ route('automation.logs', $rule) }}" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Lịch sử">📋</a>
                                    <form method="POST" action="{{ route('automation.destroy', $rule) }}" onsubmit="return confirm('Xóa quy tắc này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="Xóa">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
