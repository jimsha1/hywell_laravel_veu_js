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
       
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no')->unique();
          
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_mode')->default('cash'); // cash, bank, cheque
            
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable(); // uploaded receipt/bill
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'rejected','approved','paid'])->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'status']);
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};




