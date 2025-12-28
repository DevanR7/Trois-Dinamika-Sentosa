<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id('account_id');
            $table->string('account_number', 20)->unique();
            $table->string('account_name', 255);
            $table->enum('account_type', [
                'Aset', 
                'Liabilitas', 
                'Ekuitas', 
                'Pendapatan', 
                'HPP', 
                'Beban'
            ]);
            $table->enum('normal_balance', ['Debit', 'Kredit']);
            $table->foreignId('parent_account_id')
                ->nullable()
                ->constrained('chart_of_accounts', 'account_id')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};