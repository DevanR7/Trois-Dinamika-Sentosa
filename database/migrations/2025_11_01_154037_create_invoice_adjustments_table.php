<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices', 'invoice_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->date('adjustment_date');
            $table->enum('type', ['credit_note', 'debit_note']);
            $table->decimal('amount', 15, 2);
            $table->boolean('is_calculation_adjustment')->default(true);
            $table->text('reason');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};