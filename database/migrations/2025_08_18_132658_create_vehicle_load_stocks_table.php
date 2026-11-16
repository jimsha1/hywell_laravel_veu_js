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
        Schema::create('vehicle_load_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('v_stock_code')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('day_status')->default('started')->comment('started means day started ,closed - day closed...');
            $table->string('load_status')->nullable()->comment('this is for load status like - requested, approved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_load_stocks');
    }
};
