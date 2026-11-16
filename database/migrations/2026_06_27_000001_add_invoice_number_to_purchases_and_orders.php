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
        // Add invoice_number to purchases table
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'invoice_number')) {
            Schema::table('purchases', function (Blueprint $table) {
                
                $table->string('invoice_number')->nullable()->after('purchase_no');
                $table->index('invoice_number');
            });
        }

        // Add invoice_number to orders table
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('invoice_number')->nullable()->after('order_no');
                $table->index('invoice_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'invoice_number')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropIndex(['invoice_number']);
                $table->dropColumn('invoice_number');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['invoice_number']);
                $table->dropColumn('invoice_number');
            });
        }
    }
};
