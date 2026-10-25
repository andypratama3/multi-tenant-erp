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

                <x-filament::button wire:click="updateReport" icon="heroicon-o-arrow-path" wire:loading.attr="disabled" wire:target="updateReport">
                    <span wire:loading.remove wire:target="updateReport">Perbarui Laporan</span>
                    <span wire:loading wire:target="updateReport">Memuat...</span>
                </x-filament::button>

                <x-filament::button wire:click="export" color="gray" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled" wire:target="export">
                    <span wire:loading.remove wire:target="export">Ekspor Excel</span>
                    <span wire:loading wire:target="export">Menyiapkan...</span>
                </x-filament::button>
            </div>
        </x-filament::section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <p class="text-sm text-gray-500">Jumlah Faktur</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format((int) ($summary['invoice_count'] ?? 0), 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500">Faktur diterbitkan pada periode ini</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">Total Penjualan</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($summary['total'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">Termasuk pajak</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">Sudah Dibayar</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-success-600">{{ \App\Support\Money::formatIdr($summary['paid'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">Pembayaran diterima</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">Piutang Outstanding</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ \App\Support\Money::isPositive($summary['overdue'] ?? 0) ? 'text-danger-600' : 'text-gray-950 dark:text-white' }}">{{ \App\Support\Money::formatIdr($summary['outstanding'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ \App\Support\Money::formatIdr($summary['overdue'] ?? 0) }} lewat jatuh tempo</p>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Ringkasan Penjualan Harian</x-slot>
            <x-slot name="description">Subtotal, pajak, total, dan pembayaran per tanggal.</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm tabular-nums dark:divide-white/10">
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
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['subtotal']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['tax_amount']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">{{ \App\Support\Money::formatIdr($row['total']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['paid_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-10 text-center">
                                    <p class="font-medium text-gray-950 dark:text-white">Belum ada penjualan</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada faktur diterbitkan pada periode ini.</p>
                                    @if (auth()->user()?->can('create', \App\Domain\Sales\Models\SalesInvoice::class))
                                        <x-filament::button tag="a" :href="\App\Domain\Sales\Filament\Resources\SalesInvoiceResource::getUrl('create')" size="sm" class="mt-4">
                                            Buat Faktur Penjualan
                                        </x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (($reportData['dailySummary'] ?? []) !== [])
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold dark:border-white/10">
                                <td class="px-3 py-3">Total</td>
                                <td class="px-3 py-3 text-right">{{ number_format((int) ($summary['invoice_count'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($summary['subtotal'] ?? 0) }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($summary['tax'] ?? 0) }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($summary['total'] ?? 0) }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($summary['paid'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Produk Terlaris</x-slot>
            <x-slot name="description">10 produk dengan pendapatan tertinggi.</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm tabular-nums dark:divide-white/10">
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
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-500">{{ $row['sku'] }}</td>
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float) $row['total_quantity'], 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">{{ \App\Support\Money::formatIdr($row['total_revenue']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada produk terjual pada periode ini.</td>
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
                <table class="w-full divide-y divide-gray-200 text-sm tabular-nums dark:divide-white/10">
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
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-500">{{ $row['code'] }}</td>
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">{{ \App\Support\Money::formatIdr($row['outstanding']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['current_due']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_1_30']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_31_60']) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right {{ \App\Support\Money::isPositive($row['overdue_60_plus']) ? 'font-semibold text-danger-600 dark:text-danger-400' : '' }}">{{ \App\Support\Money::formatIdr($row['overdue_60_plus']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-10 text-center">
                                    <p class="font-medium text-gray-950 dark:text-white">Tidak ada piutang outstanding</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua faktur pada periode ini sudah dibayar.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (($reportData['arAging'] ?? []) !== [])
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold dark:border-white/10">
                                <td colspan="2" class="px-3 py-3">Total</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($summary['outstanding'] ?? 0) }}</td>
                                <td colspan="4" class="px-3 py-3 text-right text-danger-600">{{ \App\Support\Money::formatIdr($summary['overdue'] ?? 0) }} lewat jatuh tempo</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
