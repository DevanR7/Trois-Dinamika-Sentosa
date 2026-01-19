<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'pcs',
            'box',
            'unit',
            'lusin',
            'gross',
            'set',
            'kg',
            'meter',
            'roll',
            'pack'
        ];

        foreach ($units as $unitName) {
            Unit::firstOrCreate(
                ['name' => $unitName],
                ['is_active' => true]
            );
        }
    }
}