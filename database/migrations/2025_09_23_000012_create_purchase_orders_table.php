<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id('po_id');
            $table->string('po_number', 50)->unique();
            $table->string('supplier_invoice_number')->nullable();
            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id');
            $table->foreignId('requester_user_id')
                ->nullable()
                ->constrained('users', 'user_id');
            $table->foreignId('user_id_admin')
                ->constrained('users', 'user_id');
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->nullOnDelete();
            $table->boolean('apply_disc_fee')->default(false);
            $table->decimal('disc_fee_percent', 5, 2)->nullable();
            $table->decimal('disc_fee_amount', 15, 2)->nullable();
            $table->boolean('apply_rounding_discount')->default(false);
            $table->decimal('rounding_discount_amount', 15, 2)->nullable();
            $table->boolean('use_custom_dpp_factor')->default(false);
            $table->decimal('custom_dpp_factor', 12, 8)->nullable();
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->nullable();
            $table->decimal('dpp', 15, 2)->nullable();
            $table->decimal('ppn', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2)->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('total_returned', 15, 2)->default(0);
            $table->enum('status', ['draft', 'ordered', 'completed', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'paid', 'partially_paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};