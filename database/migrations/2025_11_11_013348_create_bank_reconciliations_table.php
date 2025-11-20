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
        // 1. Cukup buat tabel Bank Reconciliations saja.
        // Jangan mengotak-atik tabel general_ledgers di sini.
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id('reconciliation_id');

            $table->foreignId('chart_of_account_id')
                  ->constrained('chart_of_accounts', 'account_id');
            
            $table->foreignId('company_bank_account_id')
                  ->nullable()
                  ->constrained('company_bank_accounts', 'company_bank_account_id')
                  ->nullOnDelete();

            $table->date('statement_date'); 
            $table->decimal('statement_balance', 15, 2); 
            $table->decimal('closing_balance', 15, 2)->default(0); 
            $table->decimal('difference', 15, 2)->default(0); 
            
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