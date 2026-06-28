<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.category_edit_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70">
                <form method="POST" action="{{ route('categories.update', $category) }}" class="p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    @if($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <div>· {{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <x-input-label :value="__('expense.select_home')" />
                        <x-text-input type="text" class="mt-1 block w-full bg-slate-100/50 text-slate-500 cursor-not-allowed" :value="$category->home?->name" disabled />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('expense.type_label')" />
                        <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                            <option value="expense" @selected(old('type', $category->type) === 'expense')>{{ __('expense.type_expense') }}</option>
                            <option value="income" @selected(old('type', $category->type) === 'income')>{{ __('expense.type_income') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="parent_id" value="Danh mục cha (Tùy chọn)" />
                        <select id="parent_id" name="parent_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5">
                            <option value="">-- Đây là danh mục cha (Gốc) --</option>
                            @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id) data-type="{{ $parent->type }}">
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label for="name" :value="__('common.name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                        </div>
                        <div>
                            <x-input-label for="icon" :value="__('Icon (Emoji)')" />
                            <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full text-center text-xl" :value="old('icon', $category->icon ?: '📝')" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="color" :value="__('Màu sắc (Hex)')" />
                            <div class="flex gap-2 items-center mt-1">
                                <input id="color_picker" type="color" class="w-10 h-10 border border-slate-300 rounded-xl cursor-pointer" x-data="{ color: '{{ old('color', $category->color ?: '#6b7280') }}' }" x-model="color" @input="$refs.color_text.value = color">
                                <x-text-input id="color" name="color" type="text" class="block w-full" x-ref="color_text" :value="old('color', $category->color ?: '#6b7280')" placeholder="#6b7280" required regex="^#[0-9A-Fa-f]{6}$" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="sort_order" :value="__('Thứ tự sắp xếp')" />
                            <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="1000" class="mt-1 block w-full" :value="old('sort_order', $category->sort_order)" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('categories.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('common.save') }}</x-primary-button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const typeSelect = document.getElementById('type');
                        const parentSelect = document.getElementById('parent_id');
                        const parentOptions = Array.from(parentSelect.options);

                        function filterParents() {
                            const type = typeSelect.value;
                            parentSelect.innerHTML = '';
                            parentSelect.appendChild(parentOptions[0]);

                            parentOptions.forEach(opt => {
                                if (opt.value && opt.dataset.type === type) {
                                    parentSelect.appendChild(opt);
                                }
                            });
                        }

                        if (typeSelect && parentSelect) {
                            typeSelect.addEventListener('change', filterParents);
                            filterParents();
                        }
                    });
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
