<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void 
    {
        Schema::create('invoice_counters', function (Blueprint $table) {
            $table->id();
            $table->string('ym', 6); 
            $table->string('counter_group', 20)->default('GENERAL'); 
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique(['ym', 'counter_group']);
        });
    }
    
    public function down(): void 
    {
        Schema::dropIfExists('invoice_counters');
    }
};