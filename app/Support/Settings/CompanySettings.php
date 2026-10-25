<?php

namespace App\Support\Settings;

use App\Models\Setting;

class CompanySettings
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $defaults = [
            'company_name' => (string) config('company.name'),
            'company_address' => (string) config('company.address'),
            'company_phone' => (string) config('company.phone'),
            'company_email' => (string) config('company.email'),
            'company_tax_id' => (string) config('company.tax_id'),
            'company_bank_name' => (string) config('company.bank_name'),
            'company_bank_account' => (string) config('company.bank_account'),
            'company_bank_holder' => (string) config('company.bank_holder'),
            'period_locked_until' => '',
        ];

        $stored = Setting::query()->pluck('value', 'key')->all();

        /** @var array<string, string> $result */
        $result = [];

        foreach ($defaults as $key => $default) {
            $value = $stored[$key] ?? null;
            $result[$key] = is_string($value) && $value !== '' ? $value : $default;
        }

        return $result;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function put(array $values, ?string $updatedBy = null): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_by' => $updatedBy],
            );
        }

    }
}
