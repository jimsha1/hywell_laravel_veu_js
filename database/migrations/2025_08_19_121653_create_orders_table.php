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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->string('order_no')->nullable();
            $table->integer('total_quantity')->nullable();
            $table->decimal('total_amount',10,2)->nullable();
            $table->decimal('paid_amount',10,2)->nullable();
            $table->decimal('balance_amount',10,2)->nullable();
            $table->string('order_status')->nullable(); // ordered , delivered
            $table->string('payment_status')->nullable(); // partial , full
            $table->string('order_type')->nullable(); //Retail , wholesale
            $table->string('order_by')->nullable();
            $table->string('delivered_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
