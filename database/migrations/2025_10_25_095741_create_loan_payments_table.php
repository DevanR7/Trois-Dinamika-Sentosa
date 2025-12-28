<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('loan_id')
                ->constrained('loans', 'loan_id')
                ->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('principal_paid', 15, 2);
            $table->decimal('interest_paid', 15, 2);
            $table->decimal('total_paid', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->foreignId('interest_expense_account_id')
                ->nullable()
                ->constrained('chart_of_accounts', 'account_id')
                ->nullOnDelete();
            $table->foreignId('cash_bank_account_id')
                ->nullable()
                ->constrained('chart_of_accounts', 'account_id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};