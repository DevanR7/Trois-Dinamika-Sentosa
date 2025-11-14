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
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id('reconciliation_id');

            // Akun COA yang sedang direkonsiliasi (e.g., 1101.02 - Bank BCA)
            $table->foreignId('chart_of_account_id')
                  ->constrained('chart_of_accounts', 'account_id');
            
            // (Opsional) Tautkan juga ke CompanyBankAccount
            $table->foreignId('company_bank_account_id')
                  ->nullable()
                  ->constrained('company_bank_accounts', 'company_bank_account_id')
                  ->nullOnDelete();

            $table->date('statement_date'); // Tanggal akhir rekening koran
            $table->decimal('statement_balance', 15, 2); // Saldo akhir dari bank
            $table->decimal('closing_balance', 15, 2)->default(0); // Saldo akhir di Jurnal Umum
            $table->decimal('difference', 15, 2)->default(0); // Selisih
            
            $table->enum('status', ['draft', 'reconciled'])->default('draft');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};