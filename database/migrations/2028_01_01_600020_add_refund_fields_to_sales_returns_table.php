<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->decimal('refund_amount', 18, 2)->default(0)->after('total');
            $table->string('refund_method', 20)->default('cash')->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'refund_method']);
        });
    }
};
