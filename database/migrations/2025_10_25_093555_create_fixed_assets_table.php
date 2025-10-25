<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id('asset_id');
            $table->string('asset_name'); // misal: "Laptop Dell XPS 15"
            $table->text('description')->nullable();
            $table->date('purchase_date'); // Tanggal beli
            $table->decimal('purchase_cost', 15, 2); // Harga beli
            
            // Opsional: Nilai buku saat ini (jika Anda menerapkan depresiasi nanti)
            // $table->decimal('book_value', 15, 2); 
            
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete(); // Siapa yang mencatat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};