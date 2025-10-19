<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_change_request_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('order_change_request_id')->constrained('order_change_requests', 'request_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id');

            // Kuantitas asli (jika item sudah ada sebelumnya dan diubah/dihapus)
            $table->integer('original_quantity')->nullable();

            // Kuantitas yang diminta klien (bisa 0 jika dihapus)
            $table->integer('requested_quantity');

            // Aksi yang diminta: tambah item baru, hapus item, atau ubah kuantitas
            $table->enum('action', ['add', 'remove', 'update_qty']);

            // Simpan harga saat request dibuat (opsional, tapi bagus untuk audit)
            $table->decimal('price_per_unit', 15, 2);
            $table->decimal('subtotal', 15, 2); // requested_quantity * price_per_unit

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_change_request_items');
    }
};