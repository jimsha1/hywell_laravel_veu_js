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
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_refund_amount', 10, 4)->default(0)->after('payment_total');
            $table->decimal('refund_amount_from_order_balance', 10, 4)->default(0)->after('total_refund_amount');
            $table->decimal('balance_amount', 10, 4)->default(0)->after('refund_amount_from_order_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'total_refund_amount',
                'refund_amount_from_order_balance',
                'balance_amount',
            ]);
        });
    }
};
