<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id('loan_id');
            $table->string('lender_name');
            $table->text('description')->nullable();
            $table->date('loan_date');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->enum('status', ['active', 'paid_off'])->default('active');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->foreignId('loan_account_id')
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
        Schema::dropIfExists('loans');
    }
};