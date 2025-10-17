<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ganti nama tabel utama
        Schema::rename('sales_orders', 'orders');

        // 2. Ganti nama tabel item
        Schema::rename('sales_order_items', 'order_items');

        // 3. Sesuaikan tabel 'orders'
        Schema::table('orders', function (Blueprint $table) {
            // Buat kolom user_id_sales bisa null, karena pesanan dari klien tidak punya sales
            $table->foreignId('user_id_sales')->nullable()->change();

            // Tambahkan kolom sumber pesanan (sales atau client)
            $table->string('order_source')->default('sales')->after('status');

            // Ganti nama kolom status agar lebih umum (opsional tapi disarankan)
            // $table->renameColumn('status', 'order_status');
        });

        // 4. Sesuaikan foreign key di tabel 'order_items'
        Schema::table('order_items', function (Blueprint $table) {
            // Hapus foreign key lama
            $table->dropForeign(['order_id']);

            // Tambahkan foreign key baru yang menunjuk ke tabel 'orders'
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('order_id')->on('sales_orders')->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_source');
            $table->foreignId('user_id_sales')->nullable(false)->change();
        });

        Schema::rename('order_items', 'sales_order_items');
        Schema::rename('orders', 'sales_orders');
    }
};