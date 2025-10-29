<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_payments', function (Blueprint $table) {
            $table->id('batch_payment_id');
            $table->foreignId('client_id')->constrained('clients', 'client_id');
            $table->foreignId('processed_by_user_id')->constrained('users', 'user_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tambahkan kolom foreign key ke tabel 'payments' yang sudah ada
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('batch_payment_id')->nullable()->after('invoice_id')
                  ->constrained('batch_payments', 'batch_payment_id')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['batch_payment_id']);
            $table->dropColumn('batch_payment_id');
        });
        Schema::dropIfExists('batch_payments');
    }
};