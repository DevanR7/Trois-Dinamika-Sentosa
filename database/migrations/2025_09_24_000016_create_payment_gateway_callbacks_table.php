<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_callbacks', function (Blueprint $table) {
            $table->id('callback_id');
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('sales_invoices', 'invoice_id')
                ->onDelete('set null');
            $table->string('vendor_transaction_id');
            $table->string('status', 50);
            $table->decimal('amount', 15, 2);
            $table->string('payment_type', 50)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_callbacks');
    }
};