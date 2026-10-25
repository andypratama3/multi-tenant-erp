<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Account Codes
    |--------------------------------------------------------------------------
    |
    | Default account codes used by auto-journal integration.
    | Can be overridden via .env for different deployments.
    |
    */

    'accounts' => [
        'cash' => env('ACCT_CASH', '1-0001'),
        'bank' => env('ACCT_BANK', '1-0002'),
        'receivable' => env('ACCT_RECEIVABLE', '1-1000'),
        'inventory' => env('ACCT_INVENTORY', '1-2000'),
        'ppn_input' => env('ACCT_PPN_INPUT', '1-3000'),
        'payable' => env('ACCT_PAYABLE', '2-0001'),
        'ppn_output' => env('ACCT_PPN_OUTPUT', '2-1000'),
        'sales_revenue' => env('ACCT_SALES_REVENUE', '4-0001'),
        'cogs' => env('ACCT_COGS', '5-0001'),
        'purchase_expense' => env('ACCT_PURCHASE_EXPENSE', '5-0002'),
    ],

];
