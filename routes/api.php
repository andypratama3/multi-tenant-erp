<?php

use App\Http\Controllers\Api\AuthController;
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
| Token-authenticated JSON API. Obtain a token from POST /api/login and send
| it as `Authorization: Bearer <token>`.
|
*/

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);

    Route::apiResource('sales-orders', SalesOrderController::class)->only(['index', 'store', 'show']);
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);

    Route::apiResource('sales-invoices', SalesInvoiceController::class)->only(['index', 'store', 'show']);
    Route::post('sales-invoices/{salesInvoice}/payment', [SalesInvoiceController::class, 'recordPayment']);
    Route::get('sales-invoices/{salesInvoice}/pdf', [SalesInvoiceController::class, 'downloadPdf']);

    Route::get('stock-balances', [StockBalanceController::class, 'index']);
    Route::get('stock-balances/{productId}', [StockBalanceController::class, 'byProduct']);
});
