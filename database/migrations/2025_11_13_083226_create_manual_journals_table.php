<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_journals', function (Blueprint $table) {
            $table->id('journal_id');
            $table->string('journal_number')->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('manual_journals');
    }
};