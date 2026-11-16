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
        Schema::create('sales_return_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('sales_return_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_mode')->nullable(); // cash, credit, bank_transfer
            $table->dateTime('refund_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_payments');
    }
};




