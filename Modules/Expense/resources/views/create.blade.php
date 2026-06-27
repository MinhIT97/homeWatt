<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.create_title') }}</h2>
    </x-slot>

    @php
        $initialCategory = null;
        $oldCategoryId = old('category_id');
        if ($oldCategoryId) {
            // Find in parents or children
            foreach ($categories as $parent) {
                if ($parent->id == $oldCategoryId) {
                    $initialCategory = $parent;
                    break;
                }
                $child = $parent->children->firstWhere('id', $oldCategoryId);
                if ($child) {
                    $initialCategory = $child;
                    break;
                }
            }
        }
    @endphp

    <div class="py-12" x-data="{
        type: '{{ old('type', 'expense') }}',
        categoryId: '{{ old('category_id') }}',
        categoryName: '{{ $initialCategory ? $initialCategory->name : '' }}',
        categoryIcon: '{{ $initialCategory ? $initialCategory->icon : '' }}',
        categoryColor: '{{ $initialCategory ? $initialCategory->color : '' }}',
        showCategoryModal: false,
        activeTab: '{{ old('type', 'expense') === 'income' ? 'income' : 'expense' }}',
        searchQuery: '',

        init() {
            const debtNames = ['Cho vay', 'Trả nợ', 'Đi vay', 'Thu nợ'];
            // Also check child categories of debt parents
            if (this.categoryName) {
                if (debtNames.includes(this.categoryName)) {
                    this.activeTab = 'debt';
                }
            }
            this.$watch('type', value => {
                if (value === 'expense' || value === 'income') {
                    this.activeTab = value;
                }
            });
        },

        selectCategory(id, name, icon, color, categoryType) {
            this.categoryId = id;
            this.categoryName = name;
            this.categoryIcon = icon;
            this.categoryColor = color;
            this.type = categoryType;
            this.showCategoryModal = false;
        },

        matchesSearch(parentName, childNames, query) {
            if (!query) return true;
            const q = query.toLowerCase();
            if (parentName.includes(q)) return true;
            return childNames.some(name => name.includes(q));
        }
    }">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70">
                <form method="POST" action="{{ route('expenses.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="home_id" :value="__('expense.select_home')" />
                        <select id="home_id" name="home_id" onchange="window.location.href = '{{ route('expenses.create') }}?home_id=' + this.value" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected(old('home_id', $selectedHomeId) == $home->id)>{{ $home->name }} ({{ $home->currency }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('expense.type_label')" />
                        <select id="type" name="type" x-model="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                            <option value="expense">{{ __('expense.type_expense') }}</option>
                            <option value="income">{{ __('expense.type_income') }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="wallet_id" :value="__('expense.wallet_label')" />
                            <select id="wallet_id" name="wallet_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                                <option value="">{{ __('expense.select_wallet') }}</option>
                                @foreach($wallets as $w)
                                    <option value="{{ $w->id }}" @selected(old('wallet_id', request('wallet_id')) == $w->id)>{{ $w->name }} ({{ number_format((float) $w->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="category_id" :value="__('expense.category_label')" />
                            <input type="hidden" name="category_id" :value="categoryId" required>
                            
                            <button type="button" @click="showCategoryModal = true" class="mt-1 flex items-center justify-between w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-850 py-2.5 px-3.5 hover:border-slate-400 transition text-left focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                                <span class="flex items-center gap-2">
                                    <template x-if="categoryIcon">
                                        <span class="text-lg w-8 h-8 rounded-lg text-white flex items-center justify-center font-bold" :style="'background-color: ' + categoryColor" x-text="categoryIcon"></span>
                                    </template>
                                    <template x-if="!categoryIcon">
                                        <span class="text-lg w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center font-bold">🔍</span>
                                    </template>
                                    <span class="font-semibold text-slate-700" x-text="categoryName ? categoryName : 'Chọn danh mục...'"></span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="amount" :value="__('expense.amount_label')" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                        </div>
                        <div>
                            <x-input-label for="occurred_at" :value="__('expense.occurred_at_label')" />
                            <x-text-input id="occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" required />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('expense.description_label')" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" />
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('expenses.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('expense.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Picker Modal -->
        <div x-show="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showCategoryModal = false"></div>

            <!-- Dialog -->
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-lg sm:my-8"
                     @click.away="showCategoryModal = false"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between font-outfit">
                        <h3 class="text-lg font-bold text-slate-800">Chọn hạng mục</h3>
                        <button type="button" @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-650">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-slate-150 bg-slate-50/50">
                        <button type="button" @click="activeTab = 'expense'" :class="activeTab === 'expense' ? 'border-primary-500 text-primary-600 font-bold border-b-2 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-3.5 text-center text-sm font-semibold transition focus:outline-none">
                            Chi tiền
                        </button>
                        <button type="button" @click="activeTab = 'income'" :class="activeTab === 'income' ? 'border-primary-500 text-primary-600 font-bold border-b-2 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-3.5 text-center text-sm font-semibold transition focus:outline-none">
                            Thu tiền
                        </button>
                        <button type="button" @click="activeTab = 'debt'" :class="activeTab === 'debt' ? 'border-primary-500 text-primary-600 font-bold border-b-2 bg-white' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-3.5 text-center text-sm font-semibold transition focus:outline-none">
                            Vay nợ
                        </button>
                    </div>

                    <!-- Search box -->
                    <div class="p-4 border-b border-slate-100 bg-slate-50/30">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" x-model="searchQuery" placeholder="Tìm theo tên hạng mục..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 bg-white">
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="max-h-96 overflow-y-auto p-4 space-y-3">
                        <!-- Tab: Chi tiền -->
                        <div x-show="activeTab === 'expense'" class="space-y-4">
                            @foreach($expenseCats as $parent)
                                <div x-data="{ expanded: true }" 
                                     x-show="matchesSearch('{{ strtolower($parent->name) }}', [
                                         @foreach($parent->children as $child) '{{ strtolower($child->name) }}', @endforeach
                                     ], searchQuery)"
                                     class="space-y-1.5">
                                    <!-- Parent Category Header -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                                        <button type="button" @click="selectCategory('{{ $parent->id }}', '{{ $parent->name }}', '{{ $parent->icon }}', '{{ $parent->color }}', 'expense')" class="flex items-center gap-2.5 text-left focus:outline-none">
                                            <span class="text-sm p-1 bg-white rounded-lg border flex items-center justify-center w-7 h-7 shadow-sm">{{ $parent->icon }}</span>
                                            <span class="text-sm font-bold text-slate-800">{{ $parent->name }}</span>
                                        </button>
                                        @if($parent->children->isNotEmpty())
                                            <button type="button" @click="expanded = !expanded" class="p-1 text-slate-400 hover:text-slate-655 focus:outline-none">
                                                <svg class="w-4 h-4 transform transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                    <!-- Children List -->
                                    @if($parent->children->isNotEmpty())
                                        <div x-show="expanded" class="pl-6 grid grid-cols-2 gap-2" x-transition>
                                            @foreach($parent->children as $child)
                                                <button type="button" 
                                                        x-show="!searchQuery || '{{ strtolower($child->name) }}'.includes(searchQuery.toLowerCase())"
                                                        @click="selectCategory('{{ $child->id }}', '{{ $child->name }}', '{{ $child->icon }}', '{{ $child->color }}', 'expense')"
                                                        class="flex items-center gap-2 p-2 rounded-xl border border-slate-150 hover:bg-slate-50 hover:border-slate-300 transition text-left w-full focus:outline-none shadow-sm bg-white">
                                                    <span class="text-sm p-1.5 rounded-lg text-white flex items-center justify-center w-8 h-8 shrink-0 shadow-sm" style="background-color: {{ $child->color }}">{{ $child->icon }}</span>
                                                    <span class="text-xs font-semibold text-slate-700 truncate">{{ $child->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Tab: Thu tiền -->
                        <div x-show="activeTab === 'income'" class="space-y-4">
                            @foreach($incomeCats as $parent)
                                <div x-data="{ expanded: true }"
                                     x-show="matchesSearch('{{ strtolower($parent->name) }}', [
                                         @foreach($parent->children as $child) '{{ strtolower($child->name) }}', @endforeach
                                     ], searchQuery)"
                                     class="space-y-1.5">
                                    <!-- Parent Category Header -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                                        <button type="button" @click="selectCategory('{{ $parent->id }}', '{{ $parent->name }}', '{{ $parent->icon }}', '{{ $parent->color }}', 'income')" class="flex items-center gap-2.5 text-left focus:outline-none">
                                            <span class="text-sm p-1 bg-white rounded-lg border flex items-center justify-center w-7 h-7 shadow-sm">{{ $parent->icon }}</span>
                                            <span class="text-sm font-bold text-slate-800">{{ $parent->name }}</span>
                                        </button>
                                        @if($parent->children->isNotEmpty())
                                            <button type="button" @click="expanded = !expanded" class="p-1 text-slate-400 hover:text-slate-655 focus:outline-none">
                                                <svg class="w-4 h-4 transform transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                    <!-- Children List -->
                                    @if($parent->children->isNotEmpty())
                                        <div x-show="expanded" class="pl-6 grid grid-cols-2 gap-2" x-transition>
                                            @foreach($parent->children as $child)
                                                <button type="button"
                                                        x-show="!searchQuery || '{{ strtolower($child->name) }}'.includes(searchQuery.toLowerCase())"
                                                        @click="selectCategory('{{ $child->id }}', '{{ $child->name }}', '{{ $child->icon }}', '{{ $child->color }}', 'income')"
                                                        class="flex items-center gap-2 p-2 rounded-xl border border-slate-150 hover:bg-slate-50 hover:border-slate-300 transition text-left w-full focus:outline-none shadow-sm bg-white">
                                                    <span class="text-sm p-1.5 rounded-lg text-white flex items-center justify-center w-8 h-8 shrink-0 shadow-sm" style="background-color: {{ $child->color }}">{{ $child->icon }}</span>
                                                    <span class="text-xs font-semibold text-slate-700 truncate">{{ $child->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Tab: Vay nợ -->
                        <div x-show="activeTab === 'debt'" class="space-y-4">
                            @foreach($debtCats as $parent)
                                <div x-data="{ expanded: true }"
                                     x-show="matchesSearch('{{ strtolower($parent->name) }}', [
                                         @foreach($parent->children as $child) '{{ strtolower($child->name) }}', @endforeach
                                     ], searchQuery)"
                                     class="space-y-1.5">
                                    <!-- Parent Category Header -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                                        <button type="button" @click="selectCategory('{{ $parent->id }}', '{{ $parent->name }}', '{{ $parent->icon }}', '{{ $parent->color }}', '{{ $parent->type }}')" class="flex items-center gap-2.5 text-left focus:outline-none">
                                            <span class="text-sm p-1 bg-white rounded-lg border flex items-center justify-center w-7 h-7 shadow-sm">{{ $parent->icon }}</span>
                                            <span class="text-sm font-bold text-slate-800">{{ $parent->name }}</span>
                                        </button>
                                        @if($parent->children->isNotEmpty())
                                            <button type="button" @click="expanded = !expanded" class="p-1 text-slate-400 hover:text-slate-655 focus:outline-none">
                                                <svg class="w-4 h-4 transform transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                    <!-- Children List -->
                                    @if($parent->children->isNotEmpty())
                                        <div x-show="expanded" class="pl-6 grid grid-cols-2 gap-2" x-transition>
                                            @foreach($parent->children as $child)
                                                <button type="button"
                                                        x-show="!searchQuery || '{{ strtolower($child->name) }}'.includes(searchQuery.toLowerCase())"
                                                        @click="selectCategory('{{ $child->id }}', '{{ $child->name }}', '{{ $child->icon }}', '{{ $child->color }}', '{{ $child->type }}')"
                                                        class="flex items-center gap-2 p-2 rounded-xl border border-slate-150 hover:bg-slate-50 hover:border-slate-300 transition text-left w-full focus:outline-none shadow-sm bg-white">
                                                    <span class="text-sm p-1.5 rounded-lg text-white flex items-center justify-center w-8 h-8 shrink-0 shadow-sm" style="background-color: {{ $child->color }}">{{ $child->icon }}</span>
                                                    <span class="text-xs font-semibold text-slate-700 truncate">{{ $child->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>