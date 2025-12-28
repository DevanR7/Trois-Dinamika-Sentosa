<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciations', function (Blueprint $table) {
            $table->id('depreciation_id');
            $table->foreignId('fixed_asset_id')
                ->constrained('fixed_assets', 'asset_id')
                ->onDelete('cascade');
            $table->date('depreciation_date');
            $table->decimal('amount', 15, 2);
            $table->string('journal_group_id');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('depreciations');
    }
};