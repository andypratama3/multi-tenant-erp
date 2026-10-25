<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_note_items', function (Blueprint $table) {
            $table->string('lot_number', 100)->nullable()->after('unit_cost');
            $table->date('expiry_date')->nullable()->after('lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_note_items', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'expiry_date']);
        });
    }
};
