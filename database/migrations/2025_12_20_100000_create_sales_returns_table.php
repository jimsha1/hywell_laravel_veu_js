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
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('return_no')->nullable();
            $table->unsignedBigInteger('total_quantity')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('return_status')->default('pending'); // pending, approved, rejected
            $table->string('return_type')->nullable(); // full, partial
            $table->string('return_destination')->nullable();
            $table->unsignedBigInteger('salesman_id')->nullable();
            $table->text('return_reason')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('return_date')->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};


