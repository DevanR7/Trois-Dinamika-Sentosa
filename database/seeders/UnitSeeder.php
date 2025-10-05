<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('units')->insert([
            ['name' => 'pcs'],
            ['name' => 'box'],
            ['name' => 'unit'],
            ['name' => 'lusin'],
            ['name' => 'gross'],
        ]);
    }
}
