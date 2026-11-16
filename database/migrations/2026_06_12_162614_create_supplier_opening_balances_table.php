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
        Schema::create('supplier_opening_balances', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->dateTime('date')->default('2025-09-01 00:00:00');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_opening_balances');
    }
};
