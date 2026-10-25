<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="updateReport" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-52">
                <label for="fromDate" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Dari Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" id="fromDate" wire:model="fromDate" />
                </x-filament::input.wrapper>
                @error('fromDate') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="w-full sm:w-52">
                <label for="toDate" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Sampai Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" id="toDate" wire:model="toDate" />
                </x-filament::input.wrapper>
                @error('toDate') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <x-filament::button type="submit" icon="heroicon-o-arrow-path" wire:loading.attr="disabled" wire:target="updateReport">
                <span wire:loading.remove wire:target="updateReport">Perbarui Laporan</span>
                <span wire:loading wire:target="updateReport">Memuat...</span>
            </x-filament::button>
        </form>
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <p class="text-sm text-gray-500">Pendapatan</p>
            <p class="mt-1 text-2xl font-bold tabular-nums tabular-nums tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($incomeStatement['revenue'] ?? 0) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Beban</p>
            <p class="mt-1 text-2xl font-bold tabular-nums tabular-nums tabular-nums text-gray-950 dark:text-white">{{ \App\Support\Money::formatIdr($incomeStatement['expense'] ?? 0) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Laba Bersih</p>
            <p class="mt-1 text-2xl font-bold tabular-nums tabular-nums tabular-nums {{ \App\Support\Money::compare($incomeStatement['net_profit'] ?? 0, '0') < 0 ? 'text-danger-600' : 'text-success-600' }}">
                {{ \App\Support\Money::formatIdr($incomeStatement['net_profit'] ?? 0) }}
            </p>
        </x-filament::section>
    </div>

    <x-filament::section heading="Laba Rugi" description="Periode {{ $fromDate }} s/d {{ $toDate }}">
        <table class="w-full text-sm tabular-nums">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase text-gray-500 dark:border-white/10">
                    <th class="px-3 py-2">Akun</th>
                    <th class="px-3 py-2 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse (($incomeStatement['rows'] ?? []) as $row)
                    <tr>
                        <td class="px-3 py-2">{{ $row['code'] }} — {{ $row['name'] }}</td>
                        <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada pendapatan atau beban pada periode ini.</td>
                    </tr>
                @endforelse
                <tr class="border-t border-gray-200 font-semibold dark:border-white/10">
                    <td class="px-3 py-2">Laba Bersih</td>
                    <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($incomeStatement['net_profit'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </x-filament::section>

    <x-filament::section heading="Neraca" description="Per {{ $toDate }}">
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Aset</h3>
                <table class="w-full text-sm tabular-nums">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach (($balanceSheet['assets'] ?? []) as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['code'] }} — {{ $row['name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($row['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="px-2 py-2">Total Aset</td>
                            <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($balanceSheet['total_assets'] ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Liabilitas & Ekuitas</h3>
                <table class="w-full text-sm tabular-nums">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach (($balanceSheet['liabilities'] ?? []) as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['code'] }} — {{ $row['name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($row['amount']) }}</td>
                            </tr>
                        @endforeach
                        @foreach (($balanceSheet['equity'] ?? []) as $row)
                            <tr>
                                <td class="px-2 py-2">{{ $row['code'] }} — {{ $row['name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($row['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="px-2 py-2">Laba Berjalan</td>
                            <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($balanceSheet['net_profit'] ?? 0) }}</td>
                        </tr>
                        <tr class="font-semibold">
                            <td class="px-2 py-2">Total Liabilitas & Ekuitas</td>
                            <td class="px-2 py-2 text-right">{{ \App\Support\Money::formatIdr($balanceSheet['total_liabilities_equity'] ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-3 text-xs {{ ($balanceSheet['balanced'] ?? false) ? 'text-success-600' : 'text-danger-600' }}">
                    {{ ($balanceSheet['balanced'] ?? false) ? 'Neraca seimbang.' : 'Neraca tidak seimbang — periksa jurnal.' }}
                </p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Neraca Saldo" description="Periode {{ $fromDate }} s/d {{ $toDate }}">
        <div class="overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase text-gray-500 dark:border-white/10">
                        <th class="px-3 py-2">Akun</th>
                        <th class="px-3 py-2 text-right">Debit</th>
                        <th class="px-3 py-2 text-right">Kredit</th>
                        <th class="px-3 py-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($trialBalance as $row)
                        <tr>
                            <td class="px-3 py-2">{{ $row['code'] }} — {{ $row['name'] }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['debit']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['credit']) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Money::formatIdr($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada jurnal pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($trialBalance !== [])
                    <tfoot>
                        @php
                            $totalDebit = '0.00';
                            $totalCredit = '0.00';
                            foreach ($trialBalance as $row) {
                                $totalDebit = \App\Support\Money::add($totalDebit, $row['debit']);
                                $totalCredit = \App\Support\Money::add($totalCredit, $row['credit']);
                            }
                        @endphp
                        <tr class="border-t border-gray-200 font-semibold dark:border-white/10">
                            <td class="px-3 py-3">Total</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totalDebit) }}</td>
                            <td class="px-3 py-3 text-right">{{ \App\Support\Money::formatIdr($totalCredit) }}</td>
                            <td class="px-3 py-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
