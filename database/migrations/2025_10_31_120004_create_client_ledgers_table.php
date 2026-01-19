<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id')
                ->onDelete('restrict');
            $table->foreignId('sales_invoice_id')
                ->nullable()
                ->constrained('sales_invoices', 'invoice_id')
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
        Schema::dropIfExists('client_ledgers');
    }
};