<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filter Periode</x-slot>
            <x-slot name="description">Pilih rentang tanggal untuk ringkasan penjualan dan umur piutang.</x-slot>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="w-full sm:w-48">
                    <label for="from-date" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                        Dari Tanggal
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="from-date" type="date" wire:model="fromDate" />
                    </x-filament::input.wrapper>
                    @error('fromDate')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full sm:w-48">
                    <label for="to-date" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                        Sampai Tanggal
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="to-date" type="date" wire:model="toDate" />
                    </x-filament::input.wrapper>
                    @error('toDate')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </div>

                <x-filament::button wire:click="updateReport" icon="heroicon-o-arrow-path">
                    Perbarui Laporan
                </x-filament::button>

                <x-filament::button wire:click="export" color="gray" icon="heroicon-o-arrow-down-tray">
                    Ekspor Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ringkasan Penjualan Harian</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2 text-right">Jumlah Faktur</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                            <th class="px-3 py-2 text-right">Pajak</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-right">Dibayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($reportData['dailySummary'] ?? [] as $row)
                            <tr class="text-gray-950 dark:text-white">
                                <td class="whitespace-nowrap px-3 py-2">{{ \Illuminate\Support\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $row['invoice_count'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['subtotal'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['tax_amount'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">Rp {{ number_format((float) $row['total'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['paid_amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Produk Terlaris</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">SKU</th>
                            <th class="px-3 py-2">Nama Produk</th>
                            <th class="px-3 py-2 text-right">Total Qty</th>
                            <th class="px-3 py-2 text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($reportData['topProducts'] ?? [] as $row)
                            <tr class="text-gray-950 dark:text-white">
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $row['total_quantity'], 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">Rp {{ number_format((float) $row['total_revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada produk terjual pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Umur Piutang (AR Aging)</x-slot>
            <x-slot name="description">Posisi piutang per {{ \Illuminate\Support\Carbon::parse($toDate ?? now())->translatedFormat('d F Y') }}.</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Kode</th>
                            <th class="px-3 py-2">Nama Pelanggan</th>
                            <th class="px-3 py-2 text-right">Outstanding</th>
                            <th class="px-3 py-2 text-right">Belum Jatuh Tempo</th>
                            <th class="px-3 py-2 text-right">1–30 Hari</th>
                            <th class="px-3 py-2 text-right">31–60 Hari</th>
                            <th class="px-3 py-2 text-right">&gt;60 Hari</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($reportData['arAging'] ?? [] as $row)
                            <tr class="text-gray-950 dark:text-white">
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs">{{ $row['code'] }}</td>
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">Rp {{ number_format((float) $row['outstanding'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['current_due'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['overdue_1_30'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">Rp {{ number_format((float) $row['overdue_31_60'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right text-danger-600 dark:text-danger-400">Rp {{ number_format((float) $row['overdue_60_plus'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada piutang outstanding.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
