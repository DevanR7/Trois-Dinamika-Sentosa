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
        $table->string('journal_group_id', 50)->index();
        $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts', 'account_id');

        // GABUNGKAN DI SINI: Tipe date + Index
        $table->date('entry_date')->index(); 
        
        $table->decimal('debit', 15, 2)->default(0);
        $table->decimal('credit', 15, 2)->default(0);
        $table->string('description');

        $table->morphs('reference');

        $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
        $table->foreignId('bank_reconciliation_id')->nullable()->constrained('bank_reconciliations', 'reconciliation_id')->nullOnDelete();

        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};
