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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('ar_supplier_name')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('commercial_reg_no')->nullable();
            $table->string('vat_reg_no')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country_name')->nullable();
            $table->string('city')->nullable();
            $table->string('legal_form')->nullable();
            $table->longText('remarks')->nullable();
            $table->longText('address')->nullable();
            $table->integer('status')->default(1);

            // Activity users
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
