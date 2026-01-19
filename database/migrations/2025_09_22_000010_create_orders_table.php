<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_number')->unique();
            $table->foreignId('client_id')
                ->constrained('clients', 'client_id');
            $table->foreignId('user_id_sales')
                ->nullable()
                ->constrained('users', 'user_id');
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('sales_invoices', 'invoice_id');
            $table->date('order_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'invoiced', 'pending_review'])->default('pending');
            $table->string('order_source')->default('sales');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};