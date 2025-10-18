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
        Schema::create('payment_gateway_callbacks', function (Blueprint $table) {
            $table->id('callback_id');
            $table->foreignId('invoice_id')->constrained(table: 'sales_invoices', column: 'invoice_id');
            $table->string('vendor_transaction_id');
            $table->string('status', 50);
            $table->decimal('amount', 15, 2);
            $table->string('payment_type', 50)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_callbacks');
    }
};