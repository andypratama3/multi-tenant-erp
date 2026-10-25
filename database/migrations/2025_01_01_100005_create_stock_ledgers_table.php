<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->foreignUuid('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'opening']);
            $table->string('reference_type', 100)->nullable();
            $table->uuid('reference_id')->nullable(); // UUID morph reference
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('running_balance', 14, 3);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // Composite index per ADR 0003
            $table->index(['product_id', 'warehouse_id', 'created_at']);
        });

        // CHECK constraint: quantity cannot be zero (ADR 0003)
        DB::statement('ALTER TABLE stock_ledgers ADD CONSTRAINT chk_quantity_not_zero CHECK (quantity != 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
