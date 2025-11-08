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
        Schema::create('purchase_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders', 'po_id')->onDelete('cascade');
            
            // Ditambahkan dari migrasi create_batch_purchase_payments (->after() DIHAPUS)
            $table->foreignId('batch_purchase_payment_id')->nullable()
                  ->constrained('batch_purchase_payments', 'batch_payment_id')
                  ->onDelete('set null');

            $table->foreignId('received_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            
            // Ditambahkan dari migrasi modify_payment_tables (->after() DIHAPUS)
            $table->foreignId('payment_method_id')->nullable()
                  ->constrained('payment_methods', 'payment_method_id')
                  ->onDelete('set null');

            // DITAMBAHKAN DARI add_bank_account_id_to_payment_tables
            $table->foreignId('company_bank_account_id')
                  ->nullable()
                  ->constrained('company_bank_accounts', 'company_bank_account_id')
                  ->nullOnDelete();

            $table->enum('status', [
                'completed', 
                'pending_clearance', 
                'failed'
            ])->default('completed');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_payments');
    }
};