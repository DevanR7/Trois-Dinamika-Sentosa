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
        Schema::create('sales_orders', function (Blueprint $table) {
        $table->id('order_id'); // Primary key
        $table->string('order_number')->unique(); // Nomor pesanan yang unik, misal: SO-2025-001

        // Relasi ke tabel lain
        $table->foreignId('client_id')->constrained('clients', 'client_id');
        $table->foreignId('user_id_sales')->constrained('users', 'user_id');
        $table->foreignId('invoice_id')->nullable()->constrained('sales_invoices', 'invoice_id'); // Akan diisi saat invoice dibuat

        // Informasi pesanan
        $table->date('order_date');
        $table->decimal('total_amount', 15, 2);
        $table->enum('status', ['pending', 'approved', 'rejected', 'invoiced'])->default('pending');
        $table->text('notes')->nullable();

        $table->timestamps(); // Kolom created_at dan updated_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
