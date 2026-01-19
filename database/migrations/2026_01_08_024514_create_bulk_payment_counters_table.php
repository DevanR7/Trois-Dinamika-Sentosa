<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_payment_counters', function (Blueprint $table) {
            $table->id();
            $table->string('ym', 6);
            $table->string('type', 20); 
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique(['ym', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_payment_counters');
    }
};