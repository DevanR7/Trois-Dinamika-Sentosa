<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            
            // Invoice yang dikoreksi
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices', 'invoice_id');
            
            // User yang membuat koreksi
            $table->foreignId('user_id')->constrained('users', 'user_id');

            $table->date('adjustment_date');
            
            // Tipe: Nota Kredit (mengurangi tagihan) atau Nota Debit (menambah tagihan)
            $table->enum('type', ['credit_note', 'debit_note']);
            
            // Nilai penyesuaian (selalu positif)
            $table->decimal('amount', 15, 2);
            
            // Alasan (ini penting untuk audit)
            $table->text('reason');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};