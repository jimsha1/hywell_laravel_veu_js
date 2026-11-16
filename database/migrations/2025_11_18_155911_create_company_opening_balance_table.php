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
        Schema::create('company_opening_balance', function (Blueprint $table) {
            $table->id();
            $table->string('payment_mode')->nullable();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->dateTime('opening_date')->default('2025-08-31 00:00:00');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_opening_balance');
    }
};
