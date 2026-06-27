<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('expense.transfer_create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 bg-white/70">
                <form method="POST" action="{{ route('transfers.store') }}" class="p-8 space-y-6"
                      x-data="{
                          homeId: '{{ old('home_id', request('home_id', $selectedHomeId)) }}',
                          fromWalletId: '{{ old('from_wallet_id', request('from_wallet_id')) }}',
                          toWalletId: '{{ old('to_wallet_id') }}',
                          walletsByHome: {{ json_encode($wallets) }},
                          amountRaw: '{{ old('amount') }}',
                          amountDisplay: '',
                          feeRaw: '{{ old('fee', 0) }}',
                          feeDisplay: '0',
                          getFilteredWallets() {
                              return this.walletsByHome[this.homeId] || [];
                          },
                          init() {
                              if (this.amountRaw) {
                                  this.amountDisplay = this.formatNumber(this.amountRaw);
                              }
                              if (this.feeRaw) {
                                  this.feeDisplay = this.formatNumber(this.feeRaw);
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
                          },
                          updateFee(val) {
                              let clean = val.replace(/[^0-9]/g, '');
                              clean = clean.replace(/^0+/, '');
                              this.feeRaw = clean;
                              this.feeDisplay = this.formatNumber(clean);
                          }
                      }">
                    @csrf

                    @if(session('error'))
                        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <div>· {{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <x-input-label for="home_id" :value="__('expense.select_home')" />
                        <select id="home_id" name="home_id" x-model="homeId" @change="fromWalletId = ''; toWalletId = ''" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}">{{ $home->name }} ({{ $home->currency }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="from_wallet_id" :value="__('expense.transfer_from')" />
                            <select id="from_wallet_id" name="from_wallet_id" x-model="fromWalletId" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                                <option value="">{{ __('expense.select_wallet') }}</option>
                                <template x-for="wallet in getFilteredWallets()" :key="wallet.id">
                                    <option :value="wallet.id" x-text="wallet.name + ' (' + Number(wallet.balance).toLocaleString('vi-VN') + ')'" :selected="wallet.id == fromWalletId"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="to_wallet_id" :value="__('expense.transfer_to')" />
                            <select id="to_wallet_id" name="to_wallet_id" x-model="toWalletId" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 py-2.5 px-3.5" required>
                                <option value="">{{ __('expense.select_wallet') }}</option>
                                <template x-for="wallet in getFilteredWallets()" :key="wallet.id">
                                    <option :value="wallet.id" x-text="wallet.name + ' (' + Number(wallet.balance).toLocaleString('vi-VN') + ')'" :selected="wallet.id == toWalletId"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label for="amount_display" :value="__('expense.amount_label')" />
                            <x-text-input id="amount_display" type="text" class="mt-1 block w-full" x-model="amountDisplay" @input="updateAmount($event.target.value)" required />
                            <input type="hidden" id="amount" name="amount" :value="amountRaw">
                        </div>
                        <div>
                            <x-input-label for="fee_display" :value="__('expense.transfer_fee')" />
                            <x-text-input id="fee_display" type="text" class="mt-1 block w-full" x-model="feeDisplay" @input="updateFee($event.target.value)" />
                            <input type="hidden" id="fee" name="fee" :value="feeRaw">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="occurred_at" :value="__('expense.occurred_at_label')" />
                            <x-text-input id="occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full" :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" required />
                        </div>
                        <div>
                            <x-input-label for="description" :value="__('expense.description_label')" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('transfers.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('expense.transfer_title') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
