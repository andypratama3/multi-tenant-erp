<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('requires_expiry')->default(false)->after('is_active');
            $table->unsignedSmallInteger('default_shelf_life_days')->nullable()->after('requires_expiry');
            $table->unsignedSmallInteger('default_expiry_alert_days')->default(7)->after('default_shelf_life_days');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_expiry')->default(false)->after('is_stockable');
            $table->unsignedSmallInteger('shelf_life_days')->nullable()->after('requires_expiry');
            $table->unsignedSmallInteger('expiry_alert_days')->default(7)->after('shelf_life_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['requires_expiry', 'shelf_life_days', 'expiry_alert_days']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn(['requires_expiry', 'default_shelf_life_days', 'default_expiry_alert_days']);
        });
    }
};
