<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id('opname_id');
            $table->string('opname_number')->unique();
            $table->date('opname_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')
                ->constrained('users', 'user_id');
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->decimal('total_adjustment_value', 15, 2)->default(0);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};