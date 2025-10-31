<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->onDelete('cascade');
            
            // Relasi polimorfik opsional untuk melacak SUMBER transaksi
            $table->morphs('reference'); // Akan membuat reference_type & reference_id

            $table->date('transaction_date');
            $table->string('type'); // 'credit' (masuk) atau 'debit' (keluar)
            
            // Amount: Positif untuk kredit, Negatif untuk debit
            $table->decimal('amount', 15, 2); 
            
            $table->string('description');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->comment('User yg memproses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
    }
};