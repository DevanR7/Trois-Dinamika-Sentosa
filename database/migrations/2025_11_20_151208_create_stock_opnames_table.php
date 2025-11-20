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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id('opname_id');
            $table->string('opname_number')->unique(); // SO-202511-001
            $table->date('opname_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users', 'user_id'); // Siapa yang menghitung
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            
            // Menyimpan total nilai penyesuaian (Rupiah) untuk keperluan report cepat
            // Negatif = Rugi (Stok Hilang), Positif = Untung (Stok Lebih)
            $table->decimal('total_adjustment_value', 15, 2)->default(0); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
