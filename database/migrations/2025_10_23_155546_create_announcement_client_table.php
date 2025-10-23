<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_client', function (Blueprint $table) {
            // Foreign key ke tabel announcements (ini sudah benar karena PK-nya 'id')
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            
            // ✅ UBAH BARIS INI:
            // Tambahkan parameter kedua untuk constrained() yang berisi nama kolom PK
            $table->foreignId('client_id')->constrained(
                table: 'clients', column: 'client_id' // <-- Tentukan nama tabel dan kolom PK
            )->onDelete('cascade');
            
            // Primary key gabungan
            $table->primary(['announcement_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_client');
    }
};