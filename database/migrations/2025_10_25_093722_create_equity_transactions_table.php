<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equity_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->date('transaction_date');
            
            // 'investment' (Setoran Modal) akan MENAMBAH modal
            // 'drawing' (Penarikan/Prive) akan MENGURANGI modal
            $table->enum('type', ['investment', 'drawing']); 
            
            $table->text('description'); // misal: "Setoran modal awal Budi"
            $table->decimal('amount', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_transactions');
    }
};