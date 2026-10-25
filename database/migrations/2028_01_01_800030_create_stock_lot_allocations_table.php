<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lot_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_lot_id')
                ->constrained('stock_lots')
                ->cascadeOnDelete();
            $table->foreignUuid('stock_ledger_id')
                ->constrained('stock_ledgers')
                ->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();

            $table->unique(['stock_lot_id', 'stock_ledger_id']);
            $table->index('stock_ledger_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lot_allocations');
    }
};
