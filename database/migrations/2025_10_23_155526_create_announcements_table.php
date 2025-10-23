<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // Judul (opsional)
            $table->text('content');             // Isi pengumuman
            $table->enum('type', ['broadcast', 'targeted'])->default('broadcast'); // Tipe pengumuman
            $table->boolean('is_active')->default(false); // Status aktif/tidak
            $table->timestamps();
            $table->softDeletes(); // Tambahkan soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};