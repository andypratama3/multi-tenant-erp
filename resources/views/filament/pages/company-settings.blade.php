<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Profil Perusahaan</x-slot>
            <x-slot name="description">Data ini dipakai pada faktur PDF dan dokumen cetak.</x-slot>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="company-name" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                        Nama Perusahaan <span class="text-danger-600">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-name" wire:model="company_name" required maxlength="150" />
                    </x-filament::input.wrapper>
                    @error('company_name') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="company-address" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Alamat</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-address" wire:model="company_address" maxlength="255" />
                    </x-filament::input.wrapper>
                    @error('company_address') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company-phone" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Telepon</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-phone" wire:model="company_phone" maxlength="50" />
                    </x-filament::input.wrapper>
                    @error('company_phone') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company-email" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Email</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-email" type="email" wire:model="company_email" maxlength="150" />
                    </x-filament::input.wrapper>
                    @error('company_email') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company-tax-id" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">NPWP</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-tax-id" wire:model="company_tax_id" maxlength="50" />
                    </x-filament::input.wrapper>
                    @error('company_tax_id') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Informasi Bank</x-slot>
            <x-slot name="description">Ditampilkan pada faktur sebagai instruksi pembayaran.</x-slot>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="company-bank-name" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Nama Bank</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-bank-name" wire:model="company_bank_name" maxlength="100" />
                    </x-filament::input.wrapper>
                    @error('company_bank_name') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company-bank-account" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">No. Rekening</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-bank-account" wire:model="company_bank_account" maxlength="50" class="tabular-nums" />
                    </x-filament::input.wrapper>
                    @error('company_bank_account') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company-bank-holder" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Atas Nama</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="company-bank-holder" wire:model="company_bank_holder" maxlength="100" />
                    </x-filament::input.wrapper>
                    @error('company_bank_holder') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Kunci Periode</x-slot>
            <x-slot name="description">Jurnal dan transaksi dengan tanggal sama atau sebelum tanggal ini akan ditolak.</x-slot>

            <div class="w-full sm:w-64">
                <label for="period-locked-until" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">Terkunci Sampai</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="period-locked-until" type="date" wire:model="period_locked_until" />
                </x-filament::input.wrapper>
                @error('period_locked_until') <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Kosongkan untuk membuka semua periode.</p>
            </div>
        </x-filament::section>

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Simpan Pengaturan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </x-filament::button>
            <p class="text-xs text-gray-500 dark:text-gray-400">Perubahan berlaku untuk dokumen baru.</p>
        </div>
    </form>
</x-filament-panels::page>
