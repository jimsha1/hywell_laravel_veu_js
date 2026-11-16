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
        Schema::table('vehicle_load_stocks', function (Blueprint $table) {
            $table->timestamp('closing_req_date')->nullable()->after('approved_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_load_stocks', function (Blueprint $table) {
            $table->dropColumn('closing_req_date');
        });
    }
};
