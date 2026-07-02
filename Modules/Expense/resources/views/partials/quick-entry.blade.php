@php
    $quickHomes = ($quickHomes ?? $homes ?? collect())->values();
    $quickSelectedHomeId = (int) ($quickSelectedHomeId ?? $selectedHomeId ?? $quickHomes->first()?->id);
@endphp

@if($quickHomes->isNotEmpty() && $quickSelectedHomeId)
    <section
        x-data="homeWattQuickEntry({
            homeId: {{ $quickSelectedHomeId }},
            homes: @js($quickHomes->map(fn($home) => ['id' => $home->id, 'name' => $home->name])->values()),
            endpoints: {
                preview: '{{ route('expenses.quick.preview') }}',
                store: '{{ route('expenses.quick.store') }}',
                templates: '{{ route('expenses.quick.templates') }}',
                recurring: '{{ route('expenses.quick.recurring') }}'
            }
        })"
        x-init="init()"
        @open-quick-entry.window="openSheet()"
        class="mb-6"
    >
        <div class="bg-white/80 border border-slate-200/70 rounded-2xl shadow-sm p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row gap-3 lg:items-start">
                <div class="lg:w-44 shrink-0">
                    <select x-model.number="homeId" @change="homeChanged()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-blue-500 focus:ring-blue-500/20">
                        <template x-for="home in homes" :key="home.id">
                            <option :value="home.id" x-text="home.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex-1">
                    <textarea
                        x-model="quickText"
                        @input="dirty = true"
                        @keydown.enter="handleChatEnter($event)"
                        rows="2"
                        class="w-full resize-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                        placeholder="ăn sáng 35k tiền mặt, cho Hường Nguyễn vay 35k tech, chuyển 500k từ momo sang vcb"
                    ></textarea>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="preview()" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-slate-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z"/></svg>
                            Xem trước
                        </button>
                        <button type="button" @click="save(false)" x-show="previewItems.length" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-blue-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/></svg>
                            Lưu
                        </button>
                        <button type="button" @click="openSheet()" class="inline-flex md:hidden items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 4v16m8-8H4"/></svg>
                            Mở nhanh
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="templates.length" class="mt-4 flex gap-2 overflow-x-auto pb-1">
                <template x-for="template in templates" :key="template.id">
                    <button type="button" @click="selectTemplate(template)" class="shrink-0 inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:border-blue-300 hover:text-blue-700 transition">
                        <span x-text="template.icon"></span>
                        <span x-text="template.name"></span>
                    </button>
                </template>
            </div>

            <div x-show="activeTemplate" class="mt-4 flex flex-col sm:flex-row gap-2 sm:items-center bg-blue-50/70 border border-blue-100 rounded-xl p-3">
                <div class="text-sm font-bold text-blue-800 shrink-0" x-text="activeTemplate ? activeTemplate.name : ''"></div>
                <input x-model="templateAmount" @keydown.enter.prevent="previewTemplate()" type="text" class="flex-1 bg-white border border-blue-100 rounded-xl px-3 py-2 text-sm" placeholder="Số tiền: 35k, 1.2tr, 50000">
                <button type="button" @click="previewTemplate()" class="px-3 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold">Xem trước</button>
            </div>

            <div x-show="message" class="mt-4 rounded-xl border px-4 py-3 text-sm" :class="messageType === 'error' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700'" x-text="message"></div>

            <div x-show="previewItems.length" class="mt-4 space-y-3">
                <template x-for="(item, index) in previewItems" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <template x-if="!item.ok">
                            <div class="text-sm font-semibold text-red-600">
                                <span x-text="item.line"></span>
                                <span class="block text-xs mt-1" x-text="(item.warnings || []).join(' ')"></span>
                            </div>
                        </template>

                        <template x-if="item.ok">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold" :class="item.mode === 'transfer' ? 'bg-blue-100 text-blue-700' : (item.type === 'income' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700')" x-text="labelFor(item)"></span>
                                    <span x-show="item.category_group === 'lending' && item.counterparty" class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold">Người vay: <span x-text="item.counterparty"></span></span>
                                    <span x-show="item.duplicate" class="px-2.5 py-1 rounded-lg bg-orange-100 text-orange-700 text-xs font-bold">Có thể trùng</span>
                                    <span x-show="item.habit_suggestion" class="px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold" x-text="item.habit_suggestion"></span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                                    <template x-if="item.mode === 'transaction'">
                                        <select x-model="item.type" @change="syncCategoryForType(item)" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">
                                            <option value="expense">Chi tiêu</option>
                                            <option value="income">Thu nhập</option>
                                        </select>
                                    </template>
                                    <input x-model="item.amount" @input="dirty = true" class="rounded-xl border-slate-200 bg-white text-xs font-semibold" placeholder="Số tiền">
                                    <input x-model="item.occurred_at_input" @input="item.occurred_at = item.occurred_at_input; dirty = true" type="datetime-local" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">

                                    <template x-if="item.mode === 'transaction'">
                                        <select x-model.number="item.wallet_id" @change="setWalletName(item)" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">
                                            <template x-for="wallet in options.wallets" :key="wallet.id">
                                                <option :value="wallet.id" x-text="wallet.name"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="item.mode === 'transaction'">
                                        <select x-model.number="item.category_id" @change="setCategoryName(item)" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">
                                            <template x-for="category in categoriesFor(item.type)" :key="category.id">
                                                <option :value="category.id" x-text="categoryLabel(category)"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="item.mode === 'transfer'">
                                        <select x-model.number="item.from_wallet_id" @change="setTransferWalletName(item)" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">
                                            <template x-for="wallet in options.wallets" :key="wallet.id">
                                                <option :value="wallet.id" x-text="'Từ: ' + wallet.name"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="item.mode === 'transfer'">
                                        <select x-model.number="item.to_wallet_id" @change="setTransferWalletName(item)" class="rounded-xl border-slate-200 bg-white text-xs font-semibold">
                                            <template x-for="wallet in options.wallets" :key="wallet.id">
                                                <option :value="wallet.id" x-text="'Sang: ' + wallet.name"></option>
                                            </template>
                                        </select>
                                    </template>
                                </div>

                                <input x-model="item.description" @input="dirty = true" class="w-full rounded-xl border-slate-200 bg-white text-sm" placeholder="Ghi chú">
                                <div x-show="item.duplicate" class="text-xs text-orange-700" x-text="item.duplicate ? item.duplicate.message : ''"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="showSheet" x-cloak class="fixed inset-0 z-50 md:hidden" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/40" @click="showSheet = false"></div>
            <div class="absolute inset-x-0 bottom-0 bg-white rounded-t-3xl shadow-2xl border border-slate-200 max-h-[88vh] overflow-y-auto">
                <div class="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-4 pt-3 pb-2">
                    <div class="w-10 h-1 rounded-full bg-slate-200 mx-auto mb-3"></div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="sheetTab = 'chat'" :class="sheetTab === 'chat' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="flex-1 rounded-xl py-2 text-xs font-bold">Chat</button>
                        <button type="button" @click="sheetTab = 'form'" :class="sheetTab === 'form' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="flex-1 rounded-xl py-2 text-xs font-bold">Form</button>
                        <button type="button" @click="sheetTab = 'recurring'; loadRecurring()" :class="sheetTab === 'recurring' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" class="flex-1 rounded-xl py-2 text-xs font-bold">Định kỳ</button>
                    </div>
                </div>

                <div class="p-4 space-y-4">
                    <div x-show="sheetTab === 'chat'" class="space-y-3">
                        <textarea x-model="quickText" rows="3" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm" placeholder="cafe 35k&#10;ăn trưa 60k&#10;gửi xe 5k"></textarea>
                        <div class="flex gap-2">
                            <button type="button" @click="preview()" class="flex-1 rounded-xl bg-slate-900 text-white py-3 text-sm font-bold">Xem trước</button>
                            <button type="button" @click="save(false)" x-show="previewItems.length" class="flex-1 rounded-xl bg-blue-600 text-white py-3 text-sm font-bold">Lưu</button>
                        </div>
                    </div>

                    <div x-show="sheetTab === 'form'" class="space-y-3">
                        <input x-model="form.amount" type="text" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-lg font-extrabold" placeholder="35k">
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model.number="form.wallet_id" class="rounded-xl border-slate-200 bg-white text-sm">
                                <template x-for="wallet in options.wallets" :key="wallet.id">
                                    <option :value="wallet.id" x-text="wallet.name"></option>
                                </template>
                            </select>
                            <select x-model.number="form.category_id" @change="syncFormCategory()" class="rounded-xl border-slate-200 bg-white text-sm">
                                <template x-for="category in categoriesFor(form.type)" :key="category.id">
                                    <option :value="category.id" x-text="categoryLabel(category)"></option>
                                </template>
                            </select>
                        </div>
                        <input x-model="form.note" type="text" class="w-full rounded-xl border-slate-200 bg-white text-sm" placeholder="Ghi chú">
                        <input x-model="form.occurred_at" type="datetime-local" class="w-full rounded-xl border-slate-200 bg-white text-sm">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="setYesterday()" class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Hôm qua</button>
                            <button type="button" @click="pickWallet('tiền mặt')" class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Tiền mặt</button>
                            <button type="button" @click="pickWallet('tech')" class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Techcombank</button>
                            <button type="button" @click="pickCategory('Ăn uống')" class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Ăn uống</button>
                            <button type="button" @click="pickCategory('Xăng xe')" class="px-3 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Xăng xe</button>
                        </div>
                        <button type="button" @click="saveForm()" class="w-full rounded-2xl bg-blue-600 text-white py-3 text-sm font-bold">Lưu giao dịch</button>
                    </div>

                    <div x-show="sheetTab === 'recurring'" class="space-y-3">
                        <input x-model="recurring.name" type="text" class="w-full rounded-xl border-slate-200 bg-white text-sm" placeholder="Internet, tiền nhà, học phí">
                        <input x-model="recurring.amount" type="text" class="w-full rounded-xl border-slate-200 bg-white text-sm" placeholder="Số tiền">
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model.number="recurring.wallet_id" class="rounded-xl border-slate-200 bg-white text-sm">
                                <template x-for="wallet in options.wallets" :key="wallet.id">
                                    <option :value="wallet.id" x-text="wallet.name"></option>
                                </template>
                            </select>
                            <select x-model.number="recurring.category_id" class="rounded-xl border-slate-200 bg-white text-sm">
                                <template x-for="category in categoriesFor('expense')" :key="category.id">
                                    <option :value="category.id" x-text="categoryLabel(category)"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="recurring.frequency" class="rounded-xl border-slate-200 bg-white text-sm">
                                <option value="weekly">Hàng tuần</option>
                                <option value="monthly">Hàng tháng</option>
                                <option value="yearly">Hàng năm</option>
                            </select>
                            <input x-model="recurring.next_due_date" type="date" class="rounded-xl border-slate-200 bg-white text-sm">
                        </div>
                        <button type="button" @click="saveRecurring()" class="w-full rounded-2xl bg-slate-900 text-white py-3 text-sm font-bold">Lưu định kỳ</button>
                        <div class="space-y-2">
                            <template x-for="item in recurringItems" :key="item.id">
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                                    <div>
                                        <div class="text-sm font-bold text-slate-800" x-text="item.name"></div>
                                        <div class="text-xs text-slate-500"><span x-text="Number(item.amount).toLocaleString('vi-VN')"></span> đ · <span x-text="item.next_due_date"></span></div>
                                    </div>
                                    <button type="button" @click="deleteRecurring(item)" class="text-xs font-bold text-red-500">Tắt</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @once
        <script>
            window.homeWattQuickEntry = function(config) {
                return {
                    homeId: config.homeId,
                    homes: config.homes || [],
                    endpoints: config.endpoints,
                    quickText: '',
                    previewItems: [],
                    options: { wallets: [], categories: [], homes: [] },
                    templates: [],
                    activeTemplate: null,
                    templateAmount: '',
                    showSheet: false,
                    sheetTab: 'chat',
                    dirty: false,
                    duplicatePending: false,
                    message: '',
                    messageType: 'success',
                    form: { type: 'expense', amount: '', wallet_id: null, category_id: null, note: '', occurred_at: '' },
                    recurring: { type: 'expense', name: '', amount: '', wallet_id: null, category_id: null, frequency: 'monthly', next_due_date: '' },
                    recurringItems: [],

                    init() {
                        this.form.occurred_at = this.localDateTime();
                        this.recurring.next_due_date = new Date().toISOString().slice(0, 10);
                        this.loadTemplates();
                    },

                    csrf() {
                        return document.querySelector('meta[name="csrf-token"]').content;
                    },

                    async request(url, options = {}) {
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                ...(options.headers || {})
                            },
                            ...options
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok && response.status !== 409) {
                            const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : (payload.message || 'Không xử lý được yêu cầu.');
                            throw new Error(errors);
                        }
                        return { response, payload };
                    },

                    async homeChanged() {
                        this.previewItems = [];
                        this.activeTemplate = null;
                        await this.loadTemplates();
                    },

                    async loadTemplates() {
                        try {
                            const url = new URL(this.endpoints.templates, window.location.origin);
                            url.searchParams.set('home_id', this.homeId);
                            const { payload } = await this.request(url.toString(), { method: 'GET' });
                            this.templates = payload.templates || [];
                            this.options = payload.options || this.options;
                            this.seedFormDefaults();
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    async preview() {
                        if (!this.quickText.trim()) return;
                        try {
                            const { payload } = await this.request(this.endpoints.preview, {
                                method: 'POST',
                                body: JSON.stringify({ text: this.quickText, home_id: this.homeId })
                            });
                            this.applyPreview(payload);
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    async previewTemplate() {
                        if (!this.activeTemplate || !this.templateAmount) return;
                        try {
                            const { payload } = await this.request(this.endpoints.preview, {
                                method: 'POST',
                                body: JSON.stringify({ template_id: this.activeTemplate.id, amount: this.templateAmount, home_id: this.homeId })
                            });
                            this.applyPreview(payload);
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    applyPreview(payload) {
                        this.previewItems = payload.items || [];
                        this.options = payload.options || this.options;
                        this.templates = payload.templates || this.templates;
                        this.dirty = false;
                        this.duplicatePending = false;
                        this.message = '';
                        this.seedFormDefaults();
                    },

                    async save(force = false) {
                        if (this.duplicatePending && !force) force = true;
                        const items = this.previewItems.filter(item => item.ok);
                        if (!items.length) return;
                        try {
                            const { response, payload } = await this.request(this.endpoints.store, {
                                method: 'POST',
                                body: JSON.stringify({ items, force })
                            });
                            if (response.status === 409 && !force) {
                                this.setMessage('Có giao dịch có thể bị trùng. Bấm Lưu lần nữa để vẫn lưu.', 'error');
                                this.duplicatePending = true;
                                this.previewItems = (payload.results || []).map(result => result.item || result).filter(Boolean);
                                return;
                            }
                            this.setMessage((payload.results || []).map(item => item.label).join(' · ') || 'Đã lưu giao dịch.');
                            this.previewItems = [];
                            this.quickText = '';
                            this.activeTemplate = null;
                            this.templateAmount = '';
                            this.showSheet = false;
                            this.duplicatePending = false;
                            window.setTimeout(() => window.location.reload(), 700);
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    async saveForm() {
                        const category = this.options.categories.find(item => Number(item.id) === Number(this.form.category_id));
                        const item = {
                            ok: true,
                            mode: 'transaction',
                            home_id: this.homeId,
                            type: category ? category.type : this.form.type,
                            amount: this.form.amount,
                            wallet_id: this.form.wallet_id,
                            category_id: this.form.category_id,
                            description: this.form.note || (category ? category.name : 'Giao dịch'),
                            occurred_at: this.form.occurred_at
                        };
                        this.previewItems = [item];
                        await this.save(false);
                    },

                    async saveRecurring() {
                        try {
                            const category = this.options.categories.find(item => Number(item.id) === Number(this.recurring.category_id));
                            const { payload } = await this.request(this.endpoints.recurring, {
                                method: 'POST',
                                body: JSON.stringify({
                                    ...this.recurring,
                                    home_id: this.homeId,
                                    type: category ? category.type : 'expense',
                                    description: this.recurring.name
                                })
                            });
                            this.setMessage('Đã lưu giao dịch định kỳ: ' + payload.item.name);
                            await this.loadRecurring();
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    async loadRecurring() {
                        try {
                            const url = new URL(this.endpoints.recurring, window.location.origin);
                            url.searchParams.set('home_id', this.homeId);
                            const { payload } = await this.request(url.toString(), { method: 'GET' });
                            this.recurringItems = payload.items || [];
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    async deleteRecurring(item) {
                        try {
                            await this.request(this.endpoints.recurring + '/' + item.id, { method: 'DELETE' });
                            await this.loadRecurring();
                        } catch (error) {
                            this.setMessage(error.message, 'error');
                        }
                    },

                    handleChatEnter(event) {
                        if (event.shiftKey) return;
                        event.preventDefault();
                        if (this.previewItems.length && !this.dirty) {
                            this.save(false);
                            return;
                        }
                        this.preview();
                    },

                    selectTemplate(template) {
                        this.activeTemplate = template;
                        this.templateAmount = '';
                        this.quickText = template.name + ' ';
                        this.message = '';
                    },

                    openSheet() {
                        this.showSheet = true;
                        this.sheetTab = 'form';
                        this.seedFormDefaults();
                    },

                    categoriesFor(type) {
                        return (this.options.categories || []).filter(category => category.type === type);
                    },

                    categoryLabel(category) {
                        return (category.icon ? category.icon + ' ' : '') + category.name;
                    },

                    labelFor(item) {
                        if (item.mode === 'transfer') return 'Chuyển ví';
                        if (item.category_group === 'lending') return 'Cho vay';
                        if (item.category_group === 'debt_collection') return 'Thu nợ';
                        if (item.category_group === 'borrowing') return 'Đi vay';
                        if (item.category_group === 'debt_repayment') return 'Trả nợ';
                        return item.type === 'income' ? 'Thu nhập' : 'Chi tiêu';
                    },

                    syncCategoryForType(item) {
                        const category = this.categoriesFor(item.type)[0];
                        if (category) {
                            item.category_id = category.id;
                            item.category_name = category.name;
                            item.category_group = category.category_group;
                        }
                        this.dirty = true;
                    },

                    setWalletName(item) {
                        const wallet = this.options.wallets.find(wallet => Number(wallet.id) === Number(item.wallet_id));
                        item.wallet_name = wallet ? wallet.name : '';
                        this.dirty = true;
                    },

                    setTransferWalletName(item) {
                        const from = this.options.wallets.find(wallet => Number(wallet.id) === Number(item.from_wallet_id));
                        const to = this.options.wallets.find(wallet => Number(wallet.id) === Number(item.to_wallet_id));
                        item.from_wallet_name = from ? from.name : '';
                        item.to_wallet_name = to ? to.name : '';
                        this.dirty = true;
                    },

                    setCategoryName(item) {
                        const category = this.options.categories.find(category => Number(category.id) === Number(item.category_id));
                        item.category_name = category ? category.name : '';
                        item.category_group = category ? category.category_group : null;
                        item.type = category ? category.type : item.type;
                        this.dirty = true;
                    },

                    syncFormCategory() {
                        const category = this.options.categories.find(item => Number(item.id) === Number(this.form.category_id));
                        if (category) this.form.type = category.type;
                    },

                    seedFormDefaults() {
                        if (!this.form.wallet_id && this.options.wallets.length) this.form.wallet_id = this.options.wallets[0].id;
                        if (!this.form.category_id) {
                            const category = this.categoriesFor('expense')[0];
                            if (category) this.form.category_id = category.id;
                        }
                        if (!this.recurring.wallet_id && this.options.wallets.length) this.recurring.wallet_id = this.options.wallets[0].id;
                        if (!this.recurring.category_id) {
                            const category = this.categoriesFor('expense')[0];
                            if (category) this.recurring.category_id = category.id;
                        }
                    },

                    pickWallet(keyword) {
                        const folded = keyword.toLowerCase();
                        const wallet = this.options.wallets.find(item => item.name.toLowerCase().includes(folded) || (folded === 'tech' && item.name.toLowerCase().includes('techcombank')));
                        if (wallet) this.form.wallet_id = wallet.id;
                    },

                    pickCategory(name) {
                        const category = this.options.categories.find(item => item.name.toLowerCase().includes(name.toLowerCase()));
                        if (category) {
                            this.form.category_id = category.id;
                            this.form.type = category.type;
                        }
                    },

                    setYesterday() {
                        const date = new Date();
                        date.setDate(date.getDate() - 1);
                        this.form.occurred_at = this.localDateTime(date);
                    },

                    localDateTime(date = new Date()) {
                        const pad = value => String(value).padStart(2, '0');
                        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
                    },

                    setMessage(text, type = 'success') {
                        this.message = text;
                        this.messageType = type;
                    }
                };
            };
        </script>
    @endonce
@endif
