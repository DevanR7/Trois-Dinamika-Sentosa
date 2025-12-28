<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id('return_id');
            $table->string('return_number')->unique();
            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id');
            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders', 'po_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->date('return_date');
            $table->enum('return_handling_type', ['deduct_invoice', 'store_as_deposit'])
                ->default('deduct_invoice');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};