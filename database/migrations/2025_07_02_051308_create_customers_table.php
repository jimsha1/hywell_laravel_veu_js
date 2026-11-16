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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('customer_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_name_ar')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('commercial_reg_no')->nullable();
            $table->string('vat_reg_no')->nullable();
            $table->string('national_address_no')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('apartment_number')->nullable();
            $table->string('room_number')->nullable();
            $table->string('address')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->default(1);

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
        Schema::dropIfExists('customers');
    }
};
