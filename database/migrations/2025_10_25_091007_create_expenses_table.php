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
            $table->id('expense_id');
            $table->date('expense_date'); // Tanggal pengeluaran
            
            // Kolom kategori, bisa nullable karena kita prioritaskan chart_of_account_id
            $table->string('category')->nullable(); 
            
            $table->text('description'); // Deskripsi pengeluaran
            $table->decimal('amount', 15, 2); // Jumlah pengeluaran
            
            // ID user yang mencatat pengeluaran ini
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();
            
            // 1. Akun Beban (dari COA)
            $table->foreignId('chart_of_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete()
                  ->after('user_id');

            // 2. Sumber Dana (Kas/Bank, juga dari COA)
            $table->foreignId('cash_bank_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete()
                  ->after('chart_of_account_id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
