<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section heading="Profil Perusahaan" description="Data ini dipakai pada faktur PDF dan dokumen cetak.">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Nama Perusahaan</label>
                    <x-filament::input.wrapper>
                        <x-filament::input wire:model="company_name" />
                    </x-filament::input.wrapper>
                    @error('company_name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Alamat</label>
                    <x-filament::input.wrapper>
                        <x-filament::input wire:model="company_address" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Telepon</label>
                    <x-filament::input.wrapper><x-filament::input wire:model="company_phone" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <x-filament::input.wrapper><x-filament::input type="email" wire:model="company_email" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">NPWP</label>
                    <x-filament::input.wrapper><x-filament::input wire:model="company_tax_id" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Nama Bank</label>
                    <x-filament::input.wrapper><x-filament::input wire:model="company_bank_name" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">No. Rekening</label>
                    <x-filament::input.wrapper><x-filament::input wire:model="company_bank_account" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Atas Nama</label>
                    <x-filament::input.wrapper><x-filament::input wire:model="company_bank_holder" /></x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Kunci Periode" description="Jurnal dan transaksi dengan tanggal sama atau sebelum tanggal ini akan ditolak.">
            <div class="w-full sm:w-64">
                <label class="mb-1 block text-sm font-medium">Terkunci Sampai</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model="period_locked_until" />
                </x-filament::input.wrapper>
                @error('period_locked_until') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-gray-500">Kosongkan untuk membuka semua periode.</p>
            </div>
        </x-filament::section>

        <x-filament::button type="submit">Simpan Pengaturan</x-filament::button>
    </form>
</x-filament-panels::page>
