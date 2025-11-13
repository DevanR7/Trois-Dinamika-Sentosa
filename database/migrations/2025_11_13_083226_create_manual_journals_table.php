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
        Schema::create('manual_journals', function (Blueprint $table) {
            $table->id('journal_id');
            $table->string('journal_number')->unique(); // Nomor Jurnal (e.g., JUM-2025-0001)
            $table->date('entry_date'); // Tanggal Jurnal
            $table->text('description'); // Deskripsi/Memo
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            // $table->enum('status', ['draft', 'posted'])->default('posted'); // (Opsional jika ingin fitur draft)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_journals');
    }
};