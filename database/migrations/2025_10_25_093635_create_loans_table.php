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
            $table->decimal('remaining_balance', 15, 2); // Sisa pokok pinjaman
            $table->enum('status', ['active', 'paid_off'])->default('active');

            // Siapa yang mencatat
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();

            // === Gabungan dari modify_loans_table_for_accounting ===

            // 1. Akun Utang Pinjaman (Liabilitas, dikredit)
            $table->foreignId('loan_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            // 2. Akun Kas/Bank (Aset, didebit saat uang diterima)
            $table->foreignId('cash_bank_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
