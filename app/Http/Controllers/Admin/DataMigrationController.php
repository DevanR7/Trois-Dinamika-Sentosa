<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use App\Imports\ClientImport;
use App\Exports\TemplateProductExport;
use App\Exports\TemplateClientExport;

class DataMigrationController extends Controller
{   
    public function __construct()
    {
        $this->middleware('can:manage-data-migration');
    }

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
    
    public function downloadTemplate($type)
    {
        if ($type === 'products') {
            return Excel::download(new TemplateProductExport, 'template_products.xlsx');
        }
        if ($type === 'clients') {
            return Excel::download(new TemplateClientExport, 'template_clients.xlsx');
        }

        return back()->with('error', 'Tipe template tidak valid.');
    }
}