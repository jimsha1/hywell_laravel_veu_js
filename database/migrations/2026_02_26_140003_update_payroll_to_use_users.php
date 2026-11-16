<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update payroll to use users table directly instead of employees table
     */
    public function up(): void
    {
        // Add salary field to users table for salesman/salesexecutive
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'basic_salary')) {
                $table->decimal('basic_salary', 15, 2)->default(0)->after('phone');
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('basic_salary');
            }
            if (!Schema::hasColumn('users', 'bank_account_no')) {
                $table->string('bank_account_no')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('users', 'bank_ifsc')) {
                $table->string('bank_ifsc')->nullable()->after('bank_account_no');
            }
        });

        // Update payrolls table to use user_id instead of employee_id
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('payroll_no')->constrained('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = ['basic_salary', 'bank_name', 'bank_account_no', 'bank_ifsc'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};




