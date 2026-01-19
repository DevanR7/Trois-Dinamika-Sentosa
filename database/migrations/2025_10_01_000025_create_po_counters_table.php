<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('po_counters', function (Blueprint $table) {
        $table->id(); // GANTI INI: Gunakan ID standar
        $table->string('ym', 6);
        $table->unsignedBigInteger('supplier_id');
        $table->unsignedInteger('last_sequence')->default(0);
        $table->timestamps();
        $table->unique(['ym', 'supplier_id']); 
    });
}

    public function down(): void
    {
        Schema::dropIfExists('po_counters');
    }
};