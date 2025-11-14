<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id('asset_id');
            $table->string('asset_name');
            $table->text('description')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();

            // Akun terkait aset tetap
            $table->foreignId('fixed_asset_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            $table->foreignId('cash_bank_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            // Akun Akumulasi Penyusutan (Kontra-Aset / Saldo Kredit)
            $table->foreignId('accumulated_depreciation_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            // Akun Beban Penyusutan (Beban / Saldo Debit)
            $table->foreignId('depreciation_expense_account_id')
                  ->nullable()
                  ->constrained('chart_of_accounts', 'account_id')
                  ->nullOnDelete();

            // Metode penyusutan (misalnya: 'straight_line')
            $table->string('depreciation_method')
                  ->default('straight_line')
                  ->nullable();

            // Masa manfaat aset (dalam bulan)
            $table->integer('useful_life_months')->nullable();

            // Nilai sisa (residu)
            $table->decimal('salvage_value', 15, 2)->default(0);

            // Nilai buku saat ini (harga beli - akumulasi penyusutan)
            $table->decimal('current_book_value', 15, 2)->nullable();

            $table->timestamps();
        });

        // Inisialisasi nilai buku = harga beli untuk data awal
        DB::statement('UPDATE fixed_assets SET current_book_value = purchase_cost');
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
