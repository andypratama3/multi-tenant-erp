<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Export e-Faktur</x-slot>
        <x-slot name="description">Unduh CSV faktur pajak untuk periode tertentu. Hanya invoice dengan PPN yang disertakan.</x-slot>

        <form wire:submit="download" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-52">
                <label for="efaktur-from-date" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Dari Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="efaktur-from-date" type="date" wire:model="fromDate" />
                </x-filament::input.wrapper>
                @error('fromDate') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
            </div>

            <div class="w-full sm:w-52">
                <label for="efaktur-to-date" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Sampai Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="efaktur-to-date" type="date" wire:model="toDate" />
                </x-filament::input.wrapper>
                @error('toDate') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
            </div>

            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled" wire:target="download">
                <span wire:loading.remove wire:target="download">Unduh CSV</span>
                <span wire:loading wire:target="download">Menyiapkan...</span>
            </x-filament::button>
        </form>
    </x-filament::section>

    <x-filament::section heading="Kolom yang Diekspor" description="Pastikan data berikut sudah lengkap sebelum mengirim laporan.">
        <ul class="grid gap-3 text-sm text-gray-600 sm:grid-cols-2 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-document-text" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                <span>Nomor faktur, masa pajak, dan tanggal penerbitan.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-identification" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                <span>NPWP dan nama pelanggan.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-map-pin" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                <span>Alamat pelanggan sesuai faktur.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-filament::icon icon="heroicon-o-calculator" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                <span>DPP dan nilai PPN.</span>
            </li>
        </ul>

        <div class="mt-4 flex items-start gap-2 rounded-lg bg-warning-50 p-3 text-sm text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-300">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
            <p>Faktur tanpa NPWP pelanggan akan tetap diekspor, tetapi perlu dilengkapi sebelum dilaporkan ke DJP.</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
