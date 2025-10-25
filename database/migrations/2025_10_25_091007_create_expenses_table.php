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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id('expense_id');
            $table->date('expense_date'); // Tanggal pengeluaran
            $table->string('category'); // Kategori (Gaji, Listrik, Sewa, dll)
            $table->text('description'); // Deskripsi pengeluaran
            $table->decimal('amount', 15, 2); // Jumlah pengeluaran
            
            // ID user yang mencatat pengeluaran ini
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};