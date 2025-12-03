<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use App\Imports\ClientImport;

class DataMigrationController extends Controller
{
    public function index()
    {
        return view('admin.data_migration.index');
    }

    public function importProducts(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        
        try {
            Excel::import(new ProductImport, $request->file('file'));
            return back()->with('success', 'Data Produk berhasil diimport!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function importClients(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        
        try {
            Excel::import(new ClientImport, $request->file('file'));
            return back()->with('success', 'Data Klien berhasil diimport!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }
    
    // Fitur download template kosong agar user tidak bingung
    public function downloadTemplate($type)
    {
        // Anda bisa membuat file excel kosong manual lalu taruh di folder public/templates
        // Ini hanya redirect contoh
        $path = public_path("templates/template_{$type}.xlsx");
        if (file_exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'Template belum tersedia.');
    }
}