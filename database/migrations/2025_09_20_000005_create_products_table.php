<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id');
            $table->string('product_code', 50)->unique()->index();
            $table->string('product_name', 200)->index();
            $table->foreignId('category_id')->nullable()->constrained('categories', 'category_id')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('average_cost', 19, 4)->nullable()->default(0.00);
            $table->decimal('stock_quantity', 15, 2)->nullable();
            $table->boolean('is_active')->default(true); 
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units', 'unit_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};