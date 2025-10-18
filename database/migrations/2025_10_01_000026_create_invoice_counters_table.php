<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void {
        Schema::create('invoice_counters', function (Blueprint $table) {
            $table->string('ym', 6)->primary(); // Format YYYYMM, contoh: 202510
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('invoice_counters');
    }
};
