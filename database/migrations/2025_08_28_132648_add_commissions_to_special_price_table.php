<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('special_prices', function (Blueprint $table) {
            $table->decimal('salesman_commission')->default(0.00)->after('price');
            $table->decimal('executive_commission')->default(0.00)->after('salesman_commission');
            $table->decimal('delivery_vehicle_commission')->default(0.00)->after('executive_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_price', function (Blueprint $table) {
            $table->dropColumn([
                'salesman_commission',
                'executive_commission',
                'delivery_vehicle_commission',
            ]);
        });
    }
};
