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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('payment_method_id'); // Kita buat nama PK-nya agar konsisten
            $table->string('name'); // Misal: "Cash", "BCA Transfer", "Giro Mundur"
            
            // Tipe:
            // 'direct' = Langsung lunas (Cash, Transfer)
            // 'pending' = Perlu kliring (Giro, Cek)
            // 'gateway' = Ditangani callback (Midtrans)
            $table->enum('type', ['direct', 'pending', 'gateway'])->default('direct');
            
            // Kolom untuk admin mengaktifkan/menonaktifkan
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};