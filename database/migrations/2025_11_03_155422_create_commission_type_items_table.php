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
        Schema::create('commission_type_items', function (Blueprint $table) {
            $table->id();
            $table->string('product_type');
            $table->unsignedBigInteger('commission_type_master_id');
            $table->decimal('salesman_commission')->default(0.00);
            $table->decimal('executive_commission')->default(0.00);
            $table->decimal('delivery_vehicle_commission')->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_type_items');
    }
};
