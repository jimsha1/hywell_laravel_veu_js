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
        Schema::table('company_opening_balance', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('opening_balance')
                ->constrained('financial_years')->onDelete('restrict');
            $table->index('financial_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_opening_balance', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
            $table->dropIndex(['financial_year_id']);
            $table->dropColumn('financial_year_id');
        });
    }
};
