<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Account;
use RuntimeException;

class AccountResolver
{
    /**
     * Resolve an Account by its code from config/accounting.php.
     * Throws RuntimeException if account not found (not seeded yet).
     */
    public function resolve(string $configKey): Account
    {
        $code = config("accounting.accounts.{$configKey}");

        if (! $code) {
            throw new RuntimeException("Accounting config key '{$configKey}' is not defined.");
        }

        $account = Account::where('code', $code)->first();

        if (! $account) {
            throw new RuntimeException(
                "Account with code '{$code}' (config key: '{$configKey}') not found. Run ChartOfAccountsSeeder."
            );
        }

        return $account;
    }
}
