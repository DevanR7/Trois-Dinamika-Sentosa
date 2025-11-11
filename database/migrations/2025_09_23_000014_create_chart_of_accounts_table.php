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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id('account_id'); // Primary key
            
            // Nomor akun (e.g., "1101", "4101")
            $table->string('account_number', 20)->unique(); 
            
            // Nama akun (e.g., "Kas BCA", "Pendapatan Penjualan")
            $table->string('account_name', 255); 
            
            // Tipe akun untuk Laporan Keuangan
            $table->enum('account_type', [
                'Aset', 
                'Liabilitas', 
                'Ekuitas', 
                'Pendapatan', 
                'HPP', // Harga Pokok Penjualan
                'Beban' // Beban Operasional
            ]);
            
            // Saldo normal akun (penting untuk L/R dan Neraca)
            $table->enum('normal_balance', ['Debit', 'Kredit']);
            
            // Untuk membuat struktur hierarki (e.g., "Kas BCA" di bawah "Kas & Bank")
            $table->foreignId('parent_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};