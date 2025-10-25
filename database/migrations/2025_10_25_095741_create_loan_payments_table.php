<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id('payment_id');
            
            // Relasi ke pinjaman induk
            $table->foreignId('loan_id')->constrained('loans', 'loan_id')->onDelete('cascade');
            
            $table->date('payment_date'); // Tanggal bayar
            
            // Memecah pembayaran
            $table->decimal('principal_paid', 15, 2); // Cicilan Pokok (mengurangi utang)
            $table->decimal('interest_paid', 15, 2);  // Beban Bunga (masuk ke Laba Rugi)
            
            // Total yang dibayar (pokok + bunga)
            $table->decimal('total_paid', 15, 2); 
            
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};