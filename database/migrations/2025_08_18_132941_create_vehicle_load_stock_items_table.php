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
        Schema::create('vehicle_load_stock_items', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicle_load_stock_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('available_qty')->nullable();
            $table->integer('total_qty')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_load_stock_items');
    }
};
