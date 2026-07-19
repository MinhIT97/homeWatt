<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight font-outfit">
            Chỉnh sửa mục tiêu
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                <form method="POST" action="{{ route('goal.update', $goal->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-5">
                        <!-- Home -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ngôi nhà</label>
                            <select name="home_id" required class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                @foreach($homes as $home)
                                    <option value="{{ $home->id }}" @selected(old('home_id', $goal->home_id) == $home->id)>{{ $home->name }}</option>
                                @endforeach
                            </select>
                            @error('home_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tên mục tiêu</label>
                            <input type="text" name="name" value="{{ old('name', $goal->name) }}" required maxlength="255"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Loại mục tiêu</label>
                            <select name="type" required class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                <option value="savings" @selected(old('type', $goal->type) == 'savings')>Tiết kiệm</option>
                                <option value="debt_payoff" @selected(old('type', $goal->type) == 'debt_payoff')>Trả nợ</option>
                                <option value="energy_reduction" @selected(old('type', $goal->type) == 'energy_reduction')>Giảm tiêu thụ điện</option>
                                <option value="expense_limit" @selected(old('type', $goal->type) == 'expense_limit')>Hạn mức chi tiêu</option>
                                <option value="income_target" @selected(old('type', $goal->type) == 'income_target')>Mục tiêu thu nhập</option>
                            </select>
                            @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Target amount -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Số tiền mục tiêu (VND)</label>
                            <input type="number" name="target_amount" value="{{ old('target_amount', $goal->target_amount) }}" required min="0" step="0.01"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            @error('target_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Date range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ngày bắt đầu</label>
                                <input type="date" name="starts_at" value="{{ old('starts_at', $goal->starts_at->format('Y-m-d')) }}" required
                                    class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ngày kết thúc</label>
                                <input type="date" name="ends_at" value="{{ old('ends_at', $goal->ends_at->format('Y-m-d')) }}" required
                                    class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Icon and Color -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Biểu tượng</label>
                                <input type="text" name="icon" value="{{ old('icon', $goal->icon) }}" maxlength="10"
                                    class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Màu sắc</label>
                                <input type="color" name="color" value="{{ old('color', $goal->color) }}"
                                    class="w-full h-10 bg-slate-50 border-slate-200 rounded-xl px-2 py-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                            </div>
                        </div>

                        <!-- Category (optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Danh mục (tùy chọn)</label>
                            <select name="category_id" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                <option value="">-- Không giới hạn theo danh mục --</option>
                                @php
                                    $goalHomeIds = auth()->user()->homeMembers()->pluck('home_id');
                                    $categories = \Modules\Expense\Models\ExpenseCategory::whereIn('home_id', $goalHomeIds)->where('type', 'expense')->orderBy('name')->get();
                                @endphp
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $goal->category_id) == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Wallet (optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ví (tùy chọn)</label>
                            <select name="wallet_id" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                <option value="">-- Không giới hạn theo ví --</option>
                                @php
                                    $goalHomeIds = auth()->user()->homeMembers()->pluck('home_id');
                                    $wallets = \Modules\Wallet\Models\Wallet::whereIn('home_id', $goalHomeIds)->where('is_archived', false)->orderBy('sort_order')->get();
                                @endphp
                                @foreach($wallets as $wallet)
                                    <option value="{{ $wallet->id }}" @selected(old('wallet_id', $goal->wallet_id) == $wallet->id)>{{ $wallet->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Trạng thái</label>
                            <select name="status" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition">
                                <option value="active" @selected(old('status', $goal->status) == 'active')>Đang thực hiện</option>
                                <option value="completed" @selected(old('status', $goal->status) == 'completed')>Hoàn thành</option>
                                <option value="cancelled" @selected(old('status', $goal->status) == 'cancelled')>Đã hủy</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-slate-100">
                        <a href="{{ route('goal.show', $goal->id) }}" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 transition">Hủy</a>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-500 transition shadow-md">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
