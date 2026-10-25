<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tax_rate_id')->constrained('tax_rates')->restrictOnDelete();
            $table->string('document_type', 50)->comment('e.g. sales_invoice, purchase_order');
            $table->uuid('document_id');
            $table->decimal('base_amount', 18, 2)->comment('DPP');
            $table->decimal('tax_amount', 18, 2)->comment('Amount of tax');
            $table->date('transaction_date');
            $table->string('tax_number', 50)->nullable()->comment('Nomor faktur pajak');
            $table->enum('direction', ['in', 'out'])->comment('in=purchase, out=sale');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
