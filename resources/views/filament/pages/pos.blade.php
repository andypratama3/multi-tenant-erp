<x-filament-panels::page>
    <div class="space-y-6">
        @if ($lastInvoiceNumber)
            <x-filament::section>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/10">
                            <x-filament::icon icon="heroicon-o-check" class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Transaksi terakhir</p>
                            <p class="text-lg font-bold text-success-600">{{ $lastInvoiceNumber }} — pembayaran diterima</p>
                            @if ((float) $lastChange > 0)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Kembalian: <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($lastChange) }}</span></p>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="dismissLastReceipt" class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-300">
                        Tutup
                    </button>
                </div>
            </x-filament::section>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-5">
            <div class="min-w-0 space-y-6 lg:col-span-3">
                <x-filament::section>
                    <x-slot name="heading">Pilih Produk</x-slot>
                    <x-slot name="description">Cari produk berdasarkan nama atau SKU, lalu masukkan jumlah.</x-slot>

                    <div class="grid gap-4 sm:grid-cols-12">
                        <div class="sm:col-span-6">
                            <label for="pos-product" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Produk</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="pos-product" wire:model="productId">
                                    <option value="">— Pilih produk —</option>
                                    @foreach ($this->products() as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('productId') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="pos-quantity" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Qty</label>
                            <x-filament::input.wrapper>
                                <x-filament::input id="pos-quantity" type="number" step="0.001" min="0.001" wire:model="quantity" class="tabular-nums" />
                            </x-filament::input.wrapper>
                            @error('quantity') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-end sm:col-span-3">
                            <x-filament::button wire:click="addProduct" icon="heroicon-o-plus" class="w-full" wire:loading.attr="disabled" wire:target="addProduct">
                                <span wire:loading.remove wire:target="addProduct">Tambah</span>
                                <span wire:loading wire:target="addProduct">Menambah...</span>
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                @error('cart')
                    <div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700 ring-1 ring-danger-600/20 dark:bg-danger-950 dark:text-danger-300">{{ $message }}</div>
                @enderror

                <x-filament::section :description="'Harga, pajak, dan stok dihitung ulang oleh server saat pembayaran. '.count($cart).' item di keranjang.'">
                    <x-slot name="heading">Keranjang</x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="px-2 py-2">Produk</th>
                                    <th class="px-2 py-2 text-right">Qty</th>
                                    <th class="px-2 py-2 text-right">Harga</th>
                                    <th class="px-2 py-2 text-right">PPN</th>
                                    <th class="px-2 py-2 text-right">Total</th>
                                    <th class="px-2 py-2"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($cart as $item)
                                    @php
                                        $line = \App\Support\Money::round(\App\Support\Money::mul($item['quantity'], $item['unit_price'], 4));
                                        $lineTax = \App\Support\Money::percentage($line, $item['tax_rate']);
                                    @endphp
                                    <tr wire:key="cart-{{ $item['product_id'] }}">
                                        <td class="px-2 py-3">
                                            <p class="font-medium text-gray-950 dark:text-white">{{ $item['name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['sku'] }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-right tabular-nums">
                                            {{ $item['quantity'] }}
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">stok {{ $this->stockFor($item['product_id']) }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-right tabular-nums">{{ \App\Support\Money::formatIdr($item['unit_price']) }}</td>
                                        <td class="whitespace-nowrap px-2 py-3 text-right tabular-nums">{{ $item['tax_rate'] }}%</td>
                                        <td class="whitespace-nowrap px-2 py-3 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr(\App\Support\Money::add($line, $lineTax)) }}</td>
                                        <td class="px-2 py-3 text-right">
                                            <button type="button" wire:click="removeProduct('{{ $item['product_id'] }}')" class="rounded-md px-2 py-1 text-xs font-medium text-danger-600 transition hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-500/10">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-2 py-12 text-center">
                                            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-shopping-cart" class="size-6" />
                                            </div>
                                            <p class="mt-3 font-medium text-gray-950 dark:text-white">Keranjang masih kosong</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih produk di atas untuk memulai transaksi.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>

            <div class="min-w-0 lg:sticky lg:top-6 lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">Pembayaran</x-slot>

                    <div class="space-y-4">
                        <div>
                            <label for="pos-customer" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Pelanggan</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="pos-customer" wire:model="customerId">
                                    <option value="">— Pilih pelanggan —</option>
                                    @foreach ($this->customers() as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('customerId') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pos-warehouse" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Gudang</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="pos-warehouse" wire:model="warehouseId">
                                    @foreach ($this->warehouses() as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('warehouseId') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pos-payment-method" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Metode Pembayaran</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="pos-payment-method" wire:model="paymentMethod">
                                    <option value="cash">Tunai</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="qris">QRIS</option>
                                    <option value="other">Lainnya</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('paymentMethod') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300"><span>Subtotal</span><span class="tabular-nums">{{ \App\Support\Money::formatIdr($this->subtotal()) }}</span></div>
                            <div class="mt-1 flex justify-between text-sm text-gray-600 dark:text-gray-300"><span>PPN</span><span class="tabular-nums">{{ \App\Support\Money::formatIdr($this->taxAmount()) }}</span></div>
                            <div class="mt-3 flex justify-between border-t border-gray-200 pt-3 text-lg font-bold text-gray-950 dark:border-white/10 dark:text-white"><span>Total</span><span class="tabular-nums">{{ \App\Support\Money::formatIdr($this->total()) }}</span></div>
                        </div>

                        <div>
                            <label for="pos-amount-paid" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Uang Diterima</label>
                            <x-filament::input.wrapper>
                                <x-filament::input id="pos-amount-paid" type="number" min="0" step="100" wire:model.live="amountPaid" class="tabular-nums" />
                            </x-filament::input.wrapper>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-filament::button type="button" wire:click="setExactAmount" color="gray" size="xs">Uang pas</x-filament::button>
                                <x-filament::button type="button" wire:click="addCash(50000)" color="gray" size="xs">+50.000</x-filament::button>
                                <x-filament::button type="button" wire:click="addCash(100000)" color="gray" size="xs">+100.000</x-filament::button>
                                <x-filament::button type="button" wire:click="addCash(500000)" color="gray" size="xs">+500.000</x-filament::button>
                            </div>
                            @error('amountPaid') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-white/5">
                            <span class="text-gray-600 dark:text-gray-300">Kembalian</span>
                            <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($this->change()) }}</span>
                        </div>

                        <div>
                            <label for="pos-notes" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Catatan</label>
                            <x-filament::input.wrapper>
                                <x-filament::input id="pos-notes" wire:model="notes" placeholder="Opsional" />
                            </x-filament::input.wrapper>
                            @error('notes') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                        </div>

                        <x-filament::button
                            wire:click="process"
                            icon="heroicon-o-check-circle"
                            class="w-full"
                            size="lg"
                            wire:loading.attr="disabled"
                            wire:target="process"
                            :disabled="$processing || $cart === []"
                        >
                            <span wire:loading.remove wire:target="process">Proses Pembayaran</span>
                            <span wire:loading wire:target="process">Memproses...</span>
                        </x-filament::button>

                        <x-filament::button wire:click="clearCart" color="gray" class="w-full" size="sm" wire:loading.attr="disabled" wire:target="process" :disabled="$cart === []">
                            Kosongkan Keranjang
                        </x-filament::button>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
