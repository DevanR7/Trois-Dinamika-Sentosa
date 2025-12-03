<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id('expense_id');
            $table->date('expense_date');
            $table->string('category')->nullable(); 
            $table->text('description');
            $table->decimal('amount', 15, 2);
            
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();
            
            // Hapus ->after()
            $table->foreignId('chart_of_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            // Hapus ->after()
            $table->foreignId('cash_bank_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};