<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('invoice_id')
                ->constrained('sales_invoices', 'invoice_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->dateTime('billing_date');
            $table->enum('method', ['whatsapp', 'telepon', 'kunjungan_sales', 'email', 'payment_link_sent']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('billing_logs');
    }
};