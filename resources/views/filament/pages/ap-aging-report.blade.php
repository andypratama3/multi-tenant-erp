<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="updateReport" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-56">
                <label for="asOfDate" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    Per Tanggal
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" id="asOfDate" wire:model="asOfDate" />
                </x-filament::input.wrapper>
                @error('asOfDate')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            <x-filament::button type="submit" icon="heroicon-o-arrow-path" wire:loading.attr="disabled" wire:target="updateReport">
                <span wire:loading.remove wire:target="updateReport">Perbarui Laporan</span>
                <span wire:loading wire:target="updateReport">Memuat...</span>
            </x-filament::button>
        </form>
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <p class="text-sm text-gray-500">Total Utang</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($totals['outstanding'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-gray-500">Seluruh faktur pemasok berjalan</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Belum Jatuh Tempo</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-success-600">{{ \App\Support\Money::formatIdr($totals['current_due'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-gray-500">Masih dalam masa pembayaran</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Lewat Jatuh Tempo</p>
            <p class="mt-1 text-2xl font-bold tabular-nums {{ \App\Support\Money::isPositive($totals['overdue'] ?? 0) ? 'text-danger-600' : 'text-gray-950 dark:text-white' }}">{{ \App\Support\Money::formatIdr($totals['overdue'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-gray-500">Perlu segera dibayar</p>
        </x-filament::section>
    </div>

    <x-filament::section heading="Rincian Utang per Pemasok" description="Dikelompokkan berdasarkan umur jatuh tempo per {{ \Illuminate\Support\Carbon::parse($asOfDate)->translatedFormat('d F Y') }}.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="px-3 py-2">Kode</th>
                        <th class="px-3 py-2">Pemasok</th>
                        <th class="px-3 py-2 text-right">Belum Jatuh Tempo</th>
                        <th class="px-3 py-2 text-right">1–30 Hari</th>
                        <th class="px-3 py-2 text-right">31–60 Hari</th>
                        <th class="px-3 py-2 text-right">&gt; 60 Hari</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs text-gray-500">{{ $row['code'] }}</td>
                            <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['current_due']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_1_30']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_31_60']) }}</td>
                            <td class="px-3 py-2 text-right {{ \App\Support\Money::isPositive($row['overdue_60_plus']) ? 'font-semibold text-danger-600' : '' }}">{{ \App\Support\Money::formatIdr($row['overdue_60_plus']) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\Money::formatIdr($row['outstanding']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-10 text-center">
                                <p class="font-medium text-gray-950 dark:text-white">Tidak ada utang berjalan</p>
                                <p class="mt-1 text-sm text-gray-500">Semua faktur pemasok sudah dibayar pada tanggal ini.</p>
                                @if (auth()->user()?->can('viewAny', \App\Domain\Purchasing\Models\PurchaseInvoice::class))
                                    <x-filament::button tag="a" :href="\App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource::getUrl('index')" color="gray" size="sm" class="mt-4">
                                        Lihat Faktur Pembelian
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows !== [])
                    <tfoot>
                        <tr class="border-t border-gray-200 font-semibold dark:border-white/10">
                            <td colspan="2" class="px-3 py-3">Total</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totals['current_due'] ?? 0) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totals['overdue_1_30'] ?? 0) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totals['overdue_31_60'] ?? 0) }}</td>
                            <td class="px-3 py-3 text-right text-danger-600">{{ \App\Support\Money::formatIdr($totals['overdue_60_plus'] ?? 0) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totals['outstanding'] ?? 0) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
