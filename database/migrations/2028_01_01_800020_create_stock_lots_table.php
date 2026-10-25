<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->foreignUuid('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();
            $table->string('source_type', 100)->nullable();
            $table->uuid('source_id')->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->date('received_at');
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_received', 14, 3);
            $table->decimal('quantity_remaining', 14, 3)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'expiry_date'], 'stock_lots_fefo_index');
            $table->index(['warehouse_id', 'expiry_date'], 'stock_lots_expiry_index');
            $table->index(['source_type', 'source_id'], 'stock_lots_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
