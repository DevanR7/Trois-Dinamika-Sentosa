<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id('return_id');
            $table->string('return_number')->unique();
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id');
            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices', 'invoice_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->date('return_date');
            $table->enum('return_handling_type', ['deduct_invoice', 'store_as_credit'])
                ->default('deduct_invoice');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('total_hpp_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};