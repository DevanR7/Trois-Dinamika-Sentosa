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
            
            // Langsung ditambahkan dari migrasi add_received_by_user_id
            $table->foreignId('received_by_user_id')->nullable()->constrained('users', 'user_id');
            
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            
            // Langsung dibuat string(50) dari migrasi change_payment_method
            $table->string('payment_method', 50); 
            
            $table->string('proof_of_payment_path')->nullable();
            $table->string('transaction_id')->nullable();
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