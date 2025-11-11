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
        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');

            // ID unik untuk mengelompokkan jurnal (misal: "INV-1001")
            // Semua entri Debit/Kredit untuk 1 faktur akan punya ID grup yang sama.
            $table->string('journal_group_id', 50)->index();

            // Relasi ke Chart of Accounts
            $table->foreignId('chart_of_account_id')
                  ->constrained('chart_of_accounts', 'account_id');
            
            $table->date('entry_date'); // Tanggal transaksi
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description');

            // Relasi polimorfik ke sumber (SalesInvoice, PurchaseOrder, Expense, dll)
            $table->morphs('reference');

            // User yang mem-posting (opsional)
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};