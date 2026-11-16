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
        Schema::table('order_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('description');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('delivered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
    }
};
