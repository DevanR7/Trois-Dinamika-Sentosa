<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KODE INI HANYA MEMBUAT TABEL 'products'
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('supplier_id')->constrained(table: 'suppliers', column: 'supplier_id');
            $table->string('product_code', 50)->unique();
            $table->string('product_name', 200);
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->decimal('selling_price', 15, 2);
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units', 'unit_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};