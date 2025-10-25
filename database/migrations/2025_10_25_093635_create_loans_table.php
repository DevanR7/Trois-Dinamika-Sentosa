<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id('loan_id');
            $table->string('lender_name'); // Nama pemberi pinjaman (misal: "Bank BCA")
            $table->text('description')->nullable();
            $table->date('loan_date'); // Tanggal pinjaman diterima
            $table->decimal('principal_amount', 15, 2); // Jumlah pokok pinjaman
            
            // Sisa pokok pinjaman (akan diupdate saat ada pembayaran cicilan)
            $table->decimal('remaining_balance', 15, 2); 
            
            $table->enum('status', ['active', 'paid_off'])->default('active');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};