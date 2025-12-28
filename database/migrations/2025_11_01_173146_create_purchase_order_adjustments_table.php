<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders', 'po_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->date('adjustment_date');
            $table->enum('type', ['credit_note', 'debit_note']);
            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_adjustments');
    }
};