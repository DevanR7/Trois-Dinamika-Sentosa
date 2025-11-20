<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Handle Satuan (Default: 'pcs' jika kosong)
        $unitName = isset($row['satuan']) && trim($row['satuan']) !== '' 
                    ? $row['satuan'] 
                    : 'pcs';
                    
        $unit = Unit::firstOrCreate(['name' => $unitName]);

        // 2. Handle Supplier (Default: 'Supplier Umum' jika kosong)
        $supplierName = isset($row['nama_supplier']) && trim($row['nama_supplier']) !== '' 
                        ? $row['nama_supplier'] 
                        : 'Supplier Umum';

        $supplier = Supplier::firstOrCreate(
            ['supplier_name' => $supplierName],
            ['address' => 'Alamat default (Auto-Import)', 'phone_number' => '-']
        );

        // 3. Handle Kode Produk (Auto-Generate jika kosong)
        $productCode = $row['kode_produk'];
        if (empty($productCode)) {
            // Generate kode unik: AUTO-TIMESTAMP-RANDOM
            // Contoh: AUTO-17315678-99
            $productCode = 'AUTO-' . time() . '-' . rand(10, 99);
        }

        // 4. Simpan Produk
        // Gunakan updateOrCreate agar jika kode produk sudah ada, datanya diupdate (tidak error duplikat)
        return Product::updateOrCreate(
            ['product_code' => $productCode], // Cek berdasarkan kode
            [
                'product_name'   => $row['nama_produk'] ?? 'Produk Tanpa Nama',
                'purchase_price' => $row['harga_beli'] ?? 0,
                'selling_price'  => $row['harga_jual'] ?? 0,
                'stock_quantity' => $row['stok_awal'] ?? 0,
                'average_cost'   => $row['harga_beli'] ?? 0, // Set HPP awal = Harga Beli
                'unit_id'        => $unit->unit_id,
                'supplier_id'    => $supplier->supplier_id,
                'description'    => $row['deskripsi'] ?? null,
            ]
        );
    }
}