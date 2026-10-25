<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets (1-xxxx)
            [
                'code' => '1-0001',
                'name' => 'Kas',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '1-0002',
                'name' => 'Bank',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '1-1000',
                'name' => 'Piutang Usaha',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '1-2000',
                'name' => 'Persediaan Barang',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '1-3000',
                'name' => 'PPN Masukan',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],

            // Liabilities (2-xxxx)
            [
                'code' => '2-0001',
                'name' => 'Utang Usaha',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '2-1000',
                'name' => 'Utang Pajak PPN Keluaran',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '2-2000',
                'name' => 'Utang Pajak PPh',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],

            // Equity (3-xxxx)
            [
                'code' => '3-0001',
                'name' => 'Modal',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '3-0002',
                'name' => 'Laba Ditahan',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],

            // Revenue (4-xxxx)
            [
                'code' => '4-0001',
                'name' => 'Pendapatan Penjualan',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_leaf' => true,
                'is_active' => true,
            ],

            // COGS / Expenses (5-xxxx)
            [
                'code' => '5-0001',
                'name' => 'Harga Pokok Penjualan',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
            [
                'code' => '5-0002',
                'name' => 'Biaya Pembelian',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_leaf' => true,
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                $account,
            );
        }
    }
}
