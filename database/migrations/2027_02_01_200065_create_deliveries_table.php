<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('delivery_number', 30)->unique();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->uuid('warehouse_id')->nullable()->comment('FK to warehouses — cross-domain, no constraint');
            $table->enum('status', ['draft', 'shipped', 'delivered', 'returned'])->default('draft');
            $table->date('shipped_at')->nullable();
            $table->date('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
