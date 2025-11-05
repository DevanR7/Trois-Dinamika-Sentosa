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
            
            // ✅ PERBAIKAN 1: Dibuat nullable (karena klien bisa bayar)
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users', 'user_id');
            
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method', 50);
            
            // ✅ PERBAIKAN 2: Hapus ->after('payment_method')
            $table->string('status', 20)->default('completed');
            
            $table->text('notes')->nullable();
            
            // ✅ PERBAIKAN 3: Hapus ->after('notes')
            $table->json('details')->nullable(); 
            
            $table->timestamps();
        });

        // Bagian ini sudah benar karena menggunakan Schema::table()
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('batch_payment_id')->nullable()->after('invoice_id')
                  ->constrained('batch_payments', 'batch_payment_id')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Method down() Anda sudah benar
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['batch_payment_id']);
            $table->dropColumn('batch_payment_id');
        });
        Schema::dropIfExists('batch_payments');
    }
};