<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ GANTI NAMA TABEL: batch_purchase_payments -> bulk_purchase_payments
        Schema::create('bulk_purchase_payments', function (Blueprint $table) {
            // ✅ GANTI PK: batch_payment_id -> bulk_purchase_payment_id
            // Agar tidak ambigu dengan sales payment
            $table->id('bulk_purchase_payment_id');
            
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id');
            $table->foreignId('processed_by_user_id')->constrained('users', 'user_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);

            $table->foreignId('payment_method_id')->nullable()
                  ->constrained('payment_methods', 'payment_method_id')
                  ->onDelete('set null');
            
            $table->foreignId('company_bank_account_id')
                  ->nullable()
                  ->constrained('company_bank_accounts', 'company_bank_account_id')
                  ->nullOnDelete();
            
            // Tambahkan status agar konsisten dengan sales
            $table->string('status', 20)->default('completed');
            
            $table->text('notes')->nullable();
            
            // Tambahan kolom referensi & bukti
            $table->string('reference_number')->nullable();
            $table->string('proof_of_payment_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_purchase_payments');
    }
};