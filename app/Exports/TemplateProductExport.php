<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TemplateProductExport implements WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'product_code',
            'product_name',
            'supplier_id', 
            'unit_id',     
            'purchase_price',
            'selling_price',
            'stock_quantity',
            'description'
        ];
    }
}