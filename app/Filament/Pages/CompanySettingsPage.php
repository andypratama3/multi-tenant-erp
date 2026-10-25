<?php

namespace App\Filament\Pages;

use App\Support\Settings\CompanySettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CompanySettingsPage extends Page
{
    protected string $view = 'filament.pages.company-settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Profil & Periode';

    protected static ?int $navigationSort = 90;

    public string $company_name = '';

    public string $company_address = '';

    public string $company_phone = '';

    public string $company_email = '';

    public string $company_tax_id = '';

    public string $company_bank_name = '';

    public string $company_bank_account = '';

    public string $company_bank_holder = '';

    public string $period_locked_until = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $settings = app(CompanySettings::class)->all();

        $this->company_name = $settings['company_name'];
        $this->company_address = $settings['company_address'];
        $this->company_phone = $settings['company_phone'];
        $this->company_email = $settings['company_email'];
        $this->company_tax_id = $settings['company_tax_id'];
        $this->company_bank_name = $settings['company_bank_name'];
        $this->company_bank_account = $settings['company_bank_account'];
        $this->company_bank_holder = $settings['company_bank_holder'];
        $this->period_locked_until = $settings['period_locked_until'];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'company_bank_name' => ['nullable', 'string', 'max:100'],
            'company_bank_account' => ['nullable', 'string', 'max:50'],
            'company_bank_holder' => ['nullable', 'string', 'max:100'],
            'period_locked_until' => ['nullable', 'date'],
        ]);

        app(CompanySettings::class)->put($validated, auth()->id() !== null ? (string) auth()->id() : null);

        Notification::make()->title('Pengaturan berhasil disimpan.')->success()->send();
    }
}
