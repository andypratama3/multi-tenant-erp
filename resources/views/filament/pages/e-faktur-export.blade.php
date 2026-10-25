<x-filament-panels::page>
    <x-filament::section heading="Export e-Faktur" description="Unduh CSV faktur pajak untuk periode tertentu. Hanya invoice dengan PPN yang disertakan.">
        <form wire:submit="download" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-52">
                <label class="mb-1 block text-sm font-medium">Dari Tanggal</label>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model="fromDate" /></x-filament::input.wrapper>
                @error('fromDate') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="w-full sm:w-52">
                <label class="mb-1 block text-sm font-medium">Sampai Tanggal</label>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model="toDate" /></x-filament::input.wrapper>
                @error('toDate') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">Download CSV</x-filament::button>
        </form>
    </x-filament::section>

    <x-filament::section>
        <p class="text-sm text-gray-500">
            Format CSV mengikuti kolom e-Faktur: nomor faktur, masa pajak, NPWP, nama, alamat, DPP, dan PPN.
            Pastikan data NPWP pelanggan sudah diisi sebelum ekspor.
        </p>
    </x-filament::section>
</x-filament-panels::page>
