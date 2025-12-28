<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id')
                ->onDelete('cascade');
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders', 'po_id')
                ->onDelete('set null');
            $table->morphs('reference');
            $table->date('transaction_date');
            $table->string('type'); 
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('available');
            $table->string('description');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
    }
};