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
        Schema::create('vehicle_load_item_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_load_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('requested_qty')->nullable();
            $table->integer('approved_qty')->nullable();
            $table->dateTime('requested_date')->nullable();
            $table->string('status')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_load_item_details');
    }
};
