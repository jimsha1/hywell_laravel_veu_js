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
        Schema::create('deleted_order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_payment_id')->nullable(); // ID from order_payments table
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->decimal('balance_amount', 10, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('type')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable(); // User who deleted/edited the payment
            $table->dateTime('deleted_at')->nullable(); // When it was deleted
            $table->timestamps(); // created_at and updated_at for the deleted record
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_order_payments');
    }
};
