<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Mengganti nama `invoice_date` menjadi `order_date` agar lebih sesuai
            $table->renameColumn('invoice_date', 'order_date');

            // Menambahkan kolom untuk diskon
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percentage');

            // Menambahkan kolom catatan
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->renameColumn('order_date', 'invoice_date');
            $table->dropColumn(['discount_percentage', 'discount_amount', 'notes']);
        });
    }
};