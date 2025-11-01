<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            
            // PO yang dikoreksi
            $table->foreignId('purchase_order_id')->constrained('purchase_orders', 'po_id');
            
            // User yang membuat koreksi
            $table->foreignId('user_id')->constrained('users', 'user_id');

            $table->date('adjustment_date');
            
            // Tipe: Nota Kredit (Supplier memberi kita diskon) atau Nota Debit (Supplier menagih kita lebih)
            $table->enum('type', ['credit_note', 'debit_note']);
            
            // Nilai penyesuaian (selalu positif)
            $table->decimal('amount', 15, 2);
            
            // Alasan
            $table->text('reason');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_adjustments');
    }
};