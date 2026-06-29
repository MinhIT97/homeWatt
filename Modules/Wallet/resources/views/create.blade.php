<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('wallet.create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('wallets.store') }}" class="p-8 space-y-6"
                      x-data="{
                          amountRaw: '{{ old('opening_balance', 0) }}',
                          amountDisplay: '0',
                          icon: '{{ old('icon', '💰') }}',
                          color: '{{ old('color', '#10b981') }}',
                          presets: [
                              { name: 'Vietcombank', code: 'vcb', color: '#009f3c' },
                              { name: 'Techcombank', code: 'tcb', color: '#e02020' },
                              { name: 'VPBank', code: 'vpbank', color: '#009845' },
                              { name: 'MB Bank', code: 'mbbank', color: '#004b87' },
                              { name: 'ACB', code: 'acb', color: '#0067b2' },
                              { name: 'TPBank', code: 'tpbank', color: '#6f2c91' },
                              { name: 'BIDV', code: 'bidv', color: '#005a3c' },
                              { name: 'VietinBank', code: 'vietinbank', color: '#0072bc' },
                              { name: 'Sacombank', code: 'sacombank', color: '#00529b' },
                              { name: 'Visa', code: 'visa', color: '#1a1f71' },
                              { name: 'Mastercard', code: 'mastercard', color: '#111111' },
                              { name: 'JCB', code: 'jcb', color: '#002c87' },
                              { name: 'Napas', code: 'napas', color: '#e77817' }
                          ],
                          selectPreset(preset) {
                              this.icon = preset.code;
                              this.color = preset.color;
                          },
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

                    <div>
                        <x-input-label for="home_id" value="{{ __('wallet.select_home') }}" />
                        <select id="home_id" name="home_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="">{{ __('wallet.select_home_option') }}</option>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected(old('home_id', $selectedHomeId) == $home->id)>{{ $home->name }} ({{ $home->currency }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('home_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="{{ __('wallet.name_label') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="{{ __('wallet.name_placeholder') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="type" value="{{ __('wallet.type_label') }}" />
                        <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="cash" @selected(old('type') === 'cash')>💵 {{ __('wallet.type_cash') }}</option>
                            <option value="bank" @selected(old('type') === 'bank')>🏦 {{ __('wallet.type_bank') }}</option>
                            <option value="credit_card" @selected(old('type') === 'credit_card')>💳 {{ __('wallet.type_credit_card') }}</option>
                            <option value="overdraft" @selected(old('type') === 'overdraft')>🏦 {{ __('wallet.type_overdraft') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="opening_balance_display" value="{{ __('wallet.opening_balance') }}" />
                        <x-text-input id="opening_balance_display" type="text" class="mt-1 block w-full" x-model="amountDisplay" @input="updateAmount($event.target.value)" required />
                        <input type="hidden" id="opening_balance" name="opening_balance" :value="amountRaw">
                        <p class="text-[11px] text-slate-400 mt-1">{{ __('wallet.opening_balance_help') }}</p>
                        <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="icon" value="{{ __('wallet.icon_label') }}" />
                            <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full" x-model="icon" placeholder="💰" maxlength="10" />
                        </div>
                        <div>
                            <x-input-label for="color" value="{{ __('wallet.color_label') }}" />
                            <input id="color" name="color" type="color" class="mt-1 block w-full h-10 rounded-xl border border-slate-300" x-model="color" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Gợi ý logo ngân hàng & thẻ" class="mb-2" />
                        <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                            <template x-for="preset in presets" :key="preset.code">
                                <button type="button" @click="selectPreset(preset)" 
                                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200/60 bg-white hover:border-primary-500 hover:shadow-sm transition text-center group">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold mb-1 group-hover:scale-105 transition"
                                         :style="'background-color: ' + preset.color + '20; color: ' + preset.color">
                                        <span x-text="preset.code.toUpperCase().substring(0, 3)"></span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 truncate w-full" x-text="preset.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="{{ __('wallet.description_label') }}" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('wallets.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('wallet.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>