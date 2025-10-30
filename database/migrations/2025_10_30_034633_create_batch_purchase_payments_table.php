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
        // 1. Buat tabel master untuk batch pembayaran pembelian
        Schema::create('batch_purchase_payments', function (Blueprint $table) {
            $table->id('batch_payment_id');
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id');
            $table->foreignId('processed_by_user_id')->constrained('users', 'user_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2); // Total dana yang dialokasikan
            $table->string('payment_method', 100); // Bisa "Kredit + Transfer", dll.
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Tambahkan kolom foreign key ke tabel 'purchase_order_payments'
        Schema::table('purchase_order_payments', function (Blueprint $table) {
            $table->foreignId('batch_purchase_payment_id')->nullable()->after('po_id')
                  ->constrained('batch_purchase_payments', 'batch_payment_id')
                  ->onDelete('set null');
        });

        // 3. Tambahkan kolom 'debit_balance' (deposit kita) ke 'suppliers'
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('debit_balance', 15, 2)->default(0.00); // <-- SEPERTI INI
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('debit_balance');
        });

        Schema::table('purchase_order_payments', function (Blueprint $table) {
            $table->dropForeign(['batch_purchase_payment_id']);
            $table->dropColumn('batch_purchase_payment_id');
        });
        
        Schema::dropIfExists('batch_purchase_payments');
    }
};