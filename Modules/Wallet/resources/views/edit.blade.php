<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('wallet.edit_title') }}: {{ $wallet->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('wallets.update', $wallet) }}" class="p-8 space-y-6"
                      x-data="{
                          amountRaw: '{{ old('opening_balance', (int)$wallet->opening_balance) }}',
                          amountDisplay: '',
                          init() {
                              if (this.amountRaw) {
                                  this.amountDisplay = this.formatNumber(this.amountRaw);
                              }
                          },
                          formatNumber(val) {
                              if (!val) return '';
                              let clean = val.toString().replace(/[^0-9]/g, '');
                              clean = clean.replace(/^0+/, '');
                              if (clean === '') return '';
                              return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                          },
                          updateAmount(val) {
                              let clean = val.replace(/[^0-9]/g, '');
                              clean = clean.replace(/^0+/, '');
                              this.amountRaw = clean;
                              this.amountDisplay = this.formatNumber(clean);
                          }
                      }">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="{{ __('wallet.name_label') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $wallet->name)" required />
                    </div>

                    <div>
                        <x-input-label for="type" value="{{ __('wallet.type_label') }}" />
                        <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="cash" @selected($wallet->type === 'cash')>💵 {{ __('wallet.type_cash') }}</option>
                            <option value="bank" @selected($wallet->type === 'bank')>🏦 {{ __('wallet.type_bank') }}</option>
                            <option value="credit_card" @selected($wallet->type === 'credit_card')>💳 {{ __('wallet.type_credit_card') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="opening_balance_display" value="{{ __('wallet.opening_balance') }}" />
                        <x-text-input id="opening_balance_display" type="text" class="mt-1 block w-full" x-model="amountDisplay" @input="updateAmount($event.target.value)" required />
                        <input type="hidden" id="opening_balance" name="opening_balance" :value="amountRaw">
                        <p class="text-[11px] text-amber-600 mt-1">{{ __('wallet.opening_balance_warning') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="icon" value="{{ __('wallet.icon_label') }}" />
                            <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full" :value="old('icon', $wallet->icon)" maxlength="10" />
                        </div>
                        <div>
                            <x-input-label for="color" value="{{ __('wallet.color_label') }}" />
                            <input id="color" name="color" type="color" class="mt-1 block w-full h-10 rounded-xl border border-slate-300" value="{{ old('color', $wallet->color ?? '#10b981') }}" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="{{ __('wallet.description_label') }}" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">{{ old('description', $wallet->description) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('wallets.show', $wallet) }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('common.save_changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>