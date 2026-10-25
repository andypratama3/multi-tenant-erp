<x-filament-panels::page>
    @if ($lastInvoiceNumber)
        <x-filament::section>
            <p class="text-sm text-gray-500">Transaksi terakhir</p>
            <p class="text-lg font-bold text-success-600">{{ $lastInvoiceNumber }} — pembayaran diterima</p>
        </x-filament::section>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <x-filament::section heading="Pilih Produk">
                <div class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-6">
                        <label class="mb-1 block text-sm font-medium">Produk</label>
                        <select wire:model="productId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900">
                            <option value="">— Pilih produk —</option>
                            @foreach ($this->products() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('productId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-sm font-medium">Qty</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="0.001" min="0.001" wire:model="quantity" />
                        </x-filament::input.wrapper>
                        @error('quantity') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end sm:col-span-3">
                        <x-filament::button wire:click="addProduct" icon="heroicon-o-plus" class="w-full">
                            Tambah
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <div class="mt-6">
                <x-filament::section heading="Keranjang">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase text-gray-500 dark:border-white/10">
                                    <th class="px-2 py-2">Produk</th>
                                    <th class="px-2 py-2 text-right">Qty</th>
                                    <th class="px-2 py-2 text-right">Harga</th>
                                    <th class="px-2 py-2 text-right">PPN</th>
                                    <th class="px-2 py-2 text-right">Total</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($cart as $item)
                                    @php
                                        $line = \App\Support\Money::round(\App\Support\Money::mul($item['quantity'], $item['unit_price'], 4));
                                        $lineTax = \App\Support\Money::percentage($line, $item['tax_rate']);
                                    @endphp
                                    <tr>
                                        <td class="px-2 py-3">
                                            <p class="font-medium">{{ $item['name'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $item['sku'] }}</p>
                                        </td>
                                        <td class="px-2 py-3 text-right">{{ $item['quantity'] }}</td>
                                        <td class="px-2 py-3 text-right">{{ \App\Support\Money::formatIdr($item['unit_price']) }}</td>
                                        <td class="px-2 py-3 text-right">{{ $item['tax_rate'] }}%</td>
                                        <td class="px-2 py-3 text-right font-medium">{{ \App\Support\Money::formatIdr(\App\Support\Money::add($line, $lineTax)) }}</td>
                                        <td class="px-2 py-3 text-right">
                                            <button type="button" wire:click="removeProduct('{{ $item['product_id'] }}')" class="text-danger-600 hover:underline text-xs">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-2 py-8 text-center text-gray-500">Keranjang masih kosong.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <div class="lg:col-span-2">
            <x-filament::section heading="Pembayaran">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Pelanggan</label>
                        <select wire:model="customerId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900">
                            <option value="">— Pilih pelanggan —</option>
                            @foreach ($this->customers() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('customerId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Gudang</label>
                        <select wire:model="warehouseId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900">
                            @foreach ($this->warehouses() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('warehouseId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="flex justify-between text-sm"><span>Subtotal</span><span>{{ \App\Support\Money::formatIdr($this->subtotal()) }}</span></div>
                        <div class="mt-1 flex justify-between text-sm"><span>PPN</span><span>{{ \App\Support\Money::formatIdr($this->taxAmount()) }}</span></div>
                        <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 text-lg font-bold dark:border-white/10"><span>Total</span><span>{{ \App\Support\Money::formatIdr($this->total()) }}</span></div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Uang Diterima</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" min="0" step="100" wire:model.live="amountPaid" />
                        </x-filament::input.wrapper>
                        @error('amountPaid') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-between text-sm">
                        <span>Kembalian</span>
                        <span class="font-semibold">{{ \App\Support\Money::formatIdr($this->change()) }}</span>
                    </div>

                    <x-filament::input.wrapper>
                        <x-filament::input wire:model="notes" placeholder="Catatan (opsional)" />
                    </x-filament::input.wrapper>

                    <x-filament::button wire:click="process" icon="heroicon-o-check-circle" class="w-full" size="lg">
                        Proses Pembayaran
                    </x-filament::button>

                    <x-filament::button wire:click="clearCart" color="gray" class="w-full" size="sm">
                        Kosongkan Keranjang
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
