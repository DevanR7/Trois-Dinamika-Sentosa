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
        Schema::create('payments', function (Blueprint $table) {
        $table->id('payment_id');
        $table->foreignId('invoice_id')->constrained('sales_invoices', 'invoice_id');
        
        $table->date('payment_date');
        $table->decimal('amount', 15, 2);
        $table->enum('payment_method', ['cash', 'manual_transfer', 'payment_gateway']);
        
        // Kolom untuk bukti transfer dan payment gateway (opsional)
        $table->string('proof_of_payment_path')->nullable(); // Path file bukti transfer
        $table->string('transaction_id')->nullable(); // ID dari Midtrans nanti
        $table->enum('status', ['completed', 'pending_verification', 'failed'])->default('completed');
        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
