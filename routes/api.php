<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SalesInvoiceController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\StockBalanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ERP Samarinda
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api and use Sanctum token authentication.
| Scramble auto-generates OpenAPI docs at /docs/api.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    // Products
    Route::apiResource('products', ProductController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Sales Orders
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);

    // Sales Invoices
    Route::apiResource('sales-invoices', SalesInvoiceController::class);
    Route::post('sales-invoices/{id}/payment', [SalesInvoiceController::class, 'recordPayment']);
    Route::get('sales-invoices/{id}/pdf', [SalesInvoiceController::class, 'downloadPdf']);

    // Stock balances
    Route::get('stock-balances', [StockBalanceController::class, 'index']);
    Route::get('stock-balances/{productId}', [StockBalanceController::class, 'byProduct']);
});
