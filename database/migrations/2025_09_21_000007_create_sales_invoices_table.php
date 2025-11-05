<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('client_id')->constrained('clients', 'client_id');
            $table->foreignId('user_id_sales')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->date('order_date'); // Sebelumnya invoice_date
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('amount_paid', 15, 2)->default(0.00);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->string('pending_snap_token')->nullable();
            $table->timestamp('pending_snap_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_link')->nullable();
            $table->dateTime('payment_link_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};