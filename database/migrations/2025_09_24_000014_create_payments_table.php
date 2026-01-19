<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('invoice_id')
                ->constrained('sales_invoices', 'invoice_id')
                ->onDelete('restrict'); 
            $table->foreignId('bulk_sales_payment_id')
                ->nullable()
                ->constrained('bulk_sales_payments', 'bulk_sales_payment_id')
                ->nullOnDelete();
            $table->foreignId('received_by_user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods', 'payment_method_id')
                ->nullOnDelete();
            $table->foreignId('company_bank_account_id')
                ->nullable()
                ->constrained('company_bank_accounts', 'company_bank_account_id')
                ->nullOnDelete();
            $table->string('reference_number')->nullable()->index();
            $table->string('proof_of_payment_path')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->enum('status', ['completed', 'pending_verification', 'failed', 'pending_clearance'])->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};