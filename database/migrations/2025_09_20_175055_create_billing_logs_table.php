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
        Schema::create('billing_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('invoice_id')->constrained(table: 'sales_invoices', column: 'invoice_id');
            $table->foreignId('user_id')->constrained(table: 'users', column: 'user_id');
            $table->dateTime('billing_date');
            $table->enum('method', ['whatsapp', 'telepon', 'kunjungan_sales', 'email', 'payment_link_sent']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_logs');
    }
};
