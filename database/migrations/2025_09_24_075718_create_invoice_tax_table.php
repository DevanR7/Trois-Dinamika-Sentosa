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
         Schema::create('invoice_tax', function (Blueprint $table) {
        $table->id();
        $table->foreignId('invoice_id')->constrained('sales_invoices', 'invoice_id')->onDelete('cascade');
        $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');

        // Simpan detail pajak saat itu untuk data historis
        $table->string('name');
        $table->decimal('rate', 5, 2);
        $table->decimal('amount', 15, 2);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_tax');
    }
};
