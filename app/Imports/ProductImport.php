<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $productName = isset($row['product_name']) ? trim($row['product_name']) : '';

        if (empty($productName)) {
            return null;
        }

        $unitInput = isset($row['unit_id']) ? trim($row['unit_id']) : null;
        $unit = null;

        if (is_numeric($unitInput)) {
            $unit = Unit::find($unitInput);
        } 

        if (!$unit) {
            $unitName = !empty($unitInput) && !is_numeric($unitInput) ? $unitInput : 'pcs';
            $unit = Unit::firstOrCreate(['name' => $unitName]);
        }

        $supplierInput = isset($row['supplier_id']) ? trim($row['supplier_id']) : null;
        $supplier = null;

        if (is_numeric($supplierInput)) {
            $supplier = Supplier::find($supplierInput);
        }

        if (!$supplier) {
            $supplierName = !empty($supplierInput) && !is_numeric($supplierInput) ? $supplierInput : 'Supplier Umum';
            $supplier = Supplier::firstOrCreate(
                ['supplier_name' => $supplierName],
                ['address' => '-', 'phone_number' => '-'] 
            );
        }

        $productCode = isset($row['product_code']) ? trim($row['product_code']) : '';
        
        if (empty($productCode)) {
            $productCode = 'AUTO-' . time() . '-' . strtoupper(Str::random(4));
        }

        $purchasePrice = isset($row['purchase_price']) ? (float) $row['purchase_price'] : 0;
        $sellingPrice  = isset($row['selling_price']) ? (float) $row['selling_price'] : 0;
        $stockQuantity = isset($row['stock_quantity']) ? (float) $row['stock_quantity'] : 0;

        return Product::updateOrCreate(
            ['product_code' => $productCode], 
            [
                'product_name'   => $productName,
                'supplier_id'    => $supplier->supplier_id,
                'unit_id'        => $unit->unit_id,
                'purchase_price' => $purchasePrice,
                'selling_price'  => $sellingPrice,
                'stock_quantity' => $stockQuantity,
                'average_cost'   => DB::raw("CASE WHEN average_cost = 0 OR average_cost IS NULL THEN $purchasePrice ELSE average_cost END"),
                'description'    => $row['description'] ?? null,
            ]
        );
    }
}