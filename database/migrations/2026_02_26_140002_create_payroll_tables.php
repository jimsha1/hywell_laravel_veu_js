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
        // Employees table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Link to user if employee is also a system user
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Payroll table - Monthly salary records
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_no')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->integer('year');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0); // HRA, DA, etc.
            $table->decimal('overtime', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0); // basic + allowances + overtime + bonus
            $table->decimal('deductions', 15, 2)->default(0); // PF, ESI, Tax, etc.
            $table->decimal('net_salary', 15, 2)->default(0); // gross - deductions
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode')->nullable(); // cash, bank, cheque
            $table->string('reference_no')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled','partial'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['month', 'year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employees');
    }
};




