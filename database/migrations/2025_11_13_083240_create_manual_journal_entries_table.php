<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_journal_entries', function (Blueprint $table) {
            $table->id('entry_id');
            $table->foreignId('journal_id')
                ->constrained('manual_journals', 'journal_id')
                ->onDelete('cascade');
            $table->foreignId('chart_of_account_id')
                ->constrained('chart_of_accounts', 'account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('manual_journal_entries');
    }
};