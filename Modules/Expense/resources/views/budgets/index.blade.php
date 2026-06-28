<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Quản lý Hạn mức Chi tiêu</h2>
            <div class="flex gap-3">
                <a href="{{ route('expenses.index') }}" class="inline-flex items-center px-4 py-2 bg-white/80 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">Danh sách chi tiêu</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showModal: false,
        modalCategoryId: '',
        modalCategoryName: '',
        modalAmountDisplay: '',
        modalAmountRaw: '',
        
        formatCost(val) {
            if (!val) return '';
            let clean = val.toString().replace(/[^0-9]/g, '');
            clean = clean.replace(/^0+/, '');
            if (clean === '') return '';
            return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        updateCost(val) {
            let clean = val.replace(/[^0-9]/g, '');
            clean = clean.replace(/^0+/, '');
            this.modalAmountRaw = clean;
            this.modalAmountDisplay = this.formatCost(clean);
        },
        openBudgetModal(catId, catName, currentLimit) {
            this.modalCategoryId = catId || '';
            this.modalCategoryName = catName || 'Tổng chi tiêu (Tất cả)';
            this.modalAmountRaw = currentLimit || '';
            this.modalAmountDisplay = currentLimit ? this.formatCost(currentLimit) : '';
            this.showModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alert Success -->
            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-semibold flex items-center gap-2">
                    ✅ {{ session('success') }}
                </div>
            @endif

            <!-- Filter Bar -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6">
                <form method="GET" action="{{ route('budgets.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label for="home_id" class="text-xs font-bold text-slate-500 uppercase">Ngôi nhà</label>
                        <select name="home_id" id="home_id" onchange="this.form.submit()" class="mt-1 block w-full bg-white border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                            @foreach($homes as $h)
                                <option value="{{ $h->id }}" @selected($h->id == $selectedHomeId)>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-64">
                        <label for="month" class="text-xs font-bold text-slate-500 uppercase">Tháng quản lý</label>
                        <input type="month" name="month" id="month" value="{{ $selectedMonth }}" onchange="this.form.submit()" class="mt-1 block w-full bg-white border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" />
                    </div>
                </form>
            </div>

            <!-- Global / Total Budget Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Global Card -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-gradient-to-br from-indigo-50/50 to-purple-50/40 p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-xs font-extrabold text-indigo-500 uppercase tracking-wider">Hạn mức Tổng chi tiêu</h4>
                                <p class="text-2xl font-extrabold text-slate-800 mt-1">
                                    {{ $globalLimit > 0 ? number_format($globalLimit, 0, ',', '.') . ' đ' : 'Chưa thiết lập' }}
                                </p>
                            </div>
                            <button type="button" @click="openBudgetModal('', 'Tổng chi tiêu (Tất cả)', '{{ $globalLimit }}')" class="text-xs px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition">
                                {{ $globalLimit > 0 ? 'Sửa' : 'Thiết lập' }}
                            </button>
                        </div>
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-slate-500 font-semibold mb-1">
                                <span>Đã chi: {{ number_format($globalSpending, 0, ',', '.') }} đ</span>
                                @if($globalLimit > 0)
                                    <span>{{ min(100, round(($globalSpending / $globalLimit) * 100, 1)) }}%</span>
                                @endif
                            </div>
                            @if($globalLimit > 0)
                                @php
                                    $pct = ($globalSpending / $globalLimit) * 100;
                                    $barColor = $pct > 100 ? 'bg-red-500' : ($pct > 80 ? 'bg-amber-500' : 'bg-indigo-600');
                                @endphp
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            @else
                                <div class="w-full bg-slate-200 rounded-full h-2"></div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Budgeted Categories Total -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 flex flex-col justify-between">
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Tổng các hạn mức danh mục</h4>
                        <p class="text-2xl font-extrabold text-slate-800 mt-1">
                            {{ number_format($totalBudgetLimit, 0, ',', '.') }} đ
                        </p>
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-slate-500 font-semibold mb-1">
                                <span>Thực chi: {{ number_format($totalBudgetSpending, 0, ',', '.') }} đ</span>
                                @if($totalBudgetLimit > 0)
                                    <span>{{ min(100, round(($totalBudgetSpending / $totalBudgetLimit) * 100, 1)) }}%</span>
                                @endif
                            </div>
                            @if($totalBudgetLimit > 0)
                                @php
                                    $pct = ($totalBudgetSpending / $totalBudgetLimit) * 100;
                                    $barColor = $pct > 100 ? 'bg-red-500' : ($pct > 80 ? 'bg-amber-500' : 'bg-emerald-600');
                                @endphp
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            @else
                                <div class="w-full bg-slate-200 rounded-full h-2"></div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Helper Text Card -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 p-6 flex flex-col justify-center">
                    <div class="flex gap-3">
                        <span class="text-2xl">🔔</span>
                        <div>
                            <h5 class="text-xs font-bold text-slate-700 uppercase">Cảnh báo Telegram chủ động</h5>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Khi một danh mục chi tiêu vượt mức <strong>80%</strong> hoặc <strong>100%</strong> hạn mức đã đề ra, trợ lý Telegram sẽ ngay lập tức gửi cảnh báo đến bạn.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Budget Section -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40">
                    <h3 class="font-bold text-slate-800 font-outfit">Hạn mức chi tiết theo Danh mục</h3>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($budgetData as $item)
                            @php
                                $cat = $item['category'];
                                $limit = $item['limit'];
                                $spending = $item['spending'];
                                $pct = $item['raw_percentage'];
                                $displayPct = $item['percentage'];
                                $bgColor = 'bg-white';
                                $borderColor = 'border-slate-200/60';
                                
                                if ($limit > 0) {
                                    if ($pct > 100) {
                                        $bgColor = 'bg-red-50/30';
                                        $borderColor = 'border-red-200';
                                    } elseif ($pct > 80) {
                                        $bgColor = 'bg-amber-50/30';
                                        $borderColor = 'border-amber-200';
                                    }
                                }
                            @endphp
                            <div class="glass-panel rounded-2xl border {{ $borderColor }} {{ $bgColor }} shadow-sm p-5 flex flex-col justify-between transition hover:shadow-md duration-200">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ $cat->icon ?? '📁' }}</span>
                                            <h4 class="font-bold text-slate-800 font-outfit">{{ $cat->name }}</h4>
                                        </div>
                                        <button type="button" @click="openBudgetModal('{{ $cat->id }}', '{{ $cat->name }}', '{{ $limit }}')" class="text-xs text-primary-600 font-bold hover:text-primary-850 hover:underline">
                                            Thiết lập
                                        </button>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between text-xs text-slate-500">
                                            <span>Hạn mức:</span>
                                            <span class="font-bold text-slate-700">
                                                {{ $limit > 0 ? number_format($limit, 0, ',', '.') . ' đ' : 'Chưa thiết lập' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-xs text-slate-500">
                                            <span>Thực chi:</span>
                                            <span class="font-bold text-slate-700">{{ number_format($spending, 0, ',', '.') }} đ</span>
                                        </div>

                                        @if($limit > 0)
                                            <div class="pt-2">
                                                @php
                                                    $barColor = $pct > 100 ? 'bg-red-500' : ($pct > 80 ? 'bg-amber-500' : 'bg-primary-600');
                                                @endphp
                                                <div class="w-full bg-slate-200 rounded-full h-1.5">
                                                    <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                                                </div>
                                                <div class="flex justify-between items-center text-[10px] text-slate-450 mt-1">
                                                    <span>{{ $displayPct }}% đã dùng</span>
                                                    @if($pct > 100)
                                                        <span class="text-red-600 font-bold">Vượt {{ number_format($spending - $limit, 0, ',', '.') }} đ</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($item['budget_id'])
                                    <div class="flex justify-end pt-4 border-t border-slate-100/60 mt-4">
                                        <form method="POST" action="{{ route('budgets.destroy', $item['budget_id']) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hạn mức này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] text-red-500 font-semibold hover:text-red-700">Xóa hạn mức</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Form Modal -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showModal = false">
                    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-150 flex justify-between items-center">
                        <h3 class="text-base font-bold text-slate-850 font-outfit">Thiết lập hạn mức</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('budgets.store') }}" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="home_id" value="{{ $selectedHomeId }}" />
                        <input type="hidden" name="month" value="{{ $selectedMonth }}" />
                        <input type="hidden" name="category_id" :value="modalCategoryId" />

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase">Danh mục áp dụng</label>
                            <div class="mt-1 text-sm font-semibold text-slate-800 bg-slate-100 rounded-xl py-2 px-3 border border-slate-200" x-text="modalCategoryName"></div>
                        </div>

                        <div>
                            <x-input-label for="modal_amount_display" value="Số tiền hạn mức (VND)" />
                            <x-text-input id="modal_amount_display" type="text" class="mt-1 block w-full" x-model="modalAmountDisplay" @input="updateCost($event.target.value)" required placeholder="Ví dụ: 1.000.000" />
                            <input type="hidden" name="amount" :value="modalAmountRaw" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="showModal = false" class="text-sm font-semibold text-slate-500 hover:text-slate-800">Hủy</button>
                            <x-primary-button>Lưu lại</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
