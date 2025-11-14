<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');

            // ID unik untuk mengelompokkan jurnal (misal: "INV-1001")
            $table->string('journal_group_id', 50)->index();

            // Relasi ke Chart of Accounts
            $table->foreignId('chart_of_account_id')
                  ->constrained('chart_of_accounts', 'account_id');

            $table->date('entry_date');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description');

            // Relasi polimorfik ke sumber (SalesInvoice, PurchaseOrder, Expense, dll)
            $table->morphs('reference');

            // User yang mem-posting (opsional)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();

            // Relasi ke tabel rekonsiliasi bank (opsional)
            $table->foreignId('bank_reconciliation_id')
                  ->nullable()
                  ->constrained('bank_reconciliations', 'reconciliation_id')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};
