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

            <x-filament::button type="submit" icon="heroicon-o-arrow-path">
                Perbarui Laporan
            </x-filament::button>
        </form>
    </x-filament::section>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
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
                            <td class="px-3 py-2">{{ $row['code'] }}</td>
                            <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['current_due']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_1_30']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_31_60']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['overdue_60_plus']) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\Money::formatIdr($row['outstanding']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                                Tidak ada utang berjalan pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
