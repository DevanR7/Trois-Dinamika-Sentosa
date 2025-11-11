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

            // Relasi ke klien dan invoice asal
            $table->foreignId('client_id')
                  ->constrained('clients', 'client_id');
            $table->foreignId('sales_invoice_id')
                  ->constrained('sales_invoices', 'invoice_id');
            
            // User yang memproses retur
            $table->foreignId('user_id')
                  ->comment('User yang memproses retur')
                  ->constrained('users', 'user_id');

            $table->date('return_date');

            // Jenis penanganan retur
            $table->enum('return_handling_type', ['deduct_invoice', 'store_as_credit'])
                  ->default('deduct_invoice')
                  ->comment('Aksi: potong tagihan atau simpan jadi kredit');

            $table->text('notes')->nullable();

            // Total nominal retur (nilai penjualan yang diretur)
            $table->decimal('total_amount', 15, 2);

            // Total HPP (Harga Pokok Penjualan) dari barang yang diretur
            $table->decimal('total_hpp_amount', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
