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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('free_quantity')->default(0);
            $table->decimal('price',10,2)->nullable();
            $table->double('tax_percentage')->nullable();
            $table->decimal('tax_amount',10,2)->nullable();
            $table->decimal('total',10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
