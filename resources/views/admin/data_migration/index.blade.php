@extends('admin.layouts.app')

@section('title', 'Migrasi Data')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Migrasi Data</h1>
            <p class="page-subtitle">Import data massal (Produk & Klien) menggunakan file Excel (.xlsx)</p>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="p-4 mb-6 text-sm text-blue-800 rounded-xl bg-blue-50 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-100 dark:border-blue-800 flex items-start gap-3">
        <i class="material-icons text-[20px]">info</i>
        <div>
            <span class="font-bold">Panduan Migrasi:</span>
            <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                <li>Gunakan <b>Template Excel</b> yang telah disediakan untuk menghindari kesalahan format kolom.</li>
                <li>Pastikan tidak ada duplikasi data pada kolom unik (misal: Kode Produk, Email Klien).</li>
                <li>Proses import mungkin memakan waktu tergantung jumlah data. Jangan tutup halaman saat loading.</li>
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- CARD 1: IMPORT PRODUK --}}
        <div class="card h-full flex flex-col">
            <div class="card-header border-l-4 border-l-emerald-500">
                <div class="flex items-center gap-3">
                    {{-- REVISI: Menggunakan w-10 h-10 flex items-center justify-center agar icon simetris --}}
                    <div class="w-10 h-10 flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-lg shrink-0">
                        <i class="material-icons text-[24px]">inventory_2</i>
                    </div>
                    <h3 class="card-header-title">Import Data Produk</h3>
                </div>
            </div>
            <div class="card-body flex-1 flex flex-col">
                {{-- Step 1: Download Template --}}
                <div class="mb-6">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Langkah 1: Unduh Template</label>
                    <a href="{{ route('admin.migration.template', 'products') }}" class="btn btn-secondary w-full justify-center group">
                        <i class="material-icons text-emerald-500 group-hover:text-emerald-600 transition-colors">download</i>
                        Download Template Produk.xlsx
                    </a>
                    <p class="text-[10px] text-slate-400 mt-2 text-center">
                        Format kolom: Nama, Kode, Kategori, Harga Beli, Harga Jual, Stok, Satuan.
                    </p>
                </div>

                <hr class="border-dashed border-slate-200 dark:border-slate-700 my-6">

                {{-- Step 2: Upload Form --}}
                <form action="{{ route('admin.migration.import-products') }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col">
                    @csrf
                    
                    {{-- IMPLEMENTASI KOMPONEN FILE UPLOAD --}}
                    <div class="mb-6">
                        <x-ui.file-upload 
                            name="file"
                            label="Langkah 2: Upload File"
                            accept=".xlsx, .xls"
                            helperText="File Excel (.xlsx atau .xls). Maksimal 10MB."
                            required="true"
                            variant="dropzone" 
                        />
                    </div>

                    <div class="mt-auto">
                        <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-emerald-500/20">
                            <i class="material-icons text-[18px]">upload_file</i>
                            Mulai Import Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARD 2: IMPORT KLIEN --}}
        <div class="card h-full flex flex-col">
            <div class="card-header border-l-4 border-l-indigo-500">
                <div class="flex items-center gap-3">
                    {{-- REVISI: Menggunakan w-10 h-10 flex items-center justify-center agar icon simetris --}}
                    <div class="w-10 h-10 flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-lg shrink-0">
                        <i class="material-icons text-[24px]">group</i>
                    </div>
                    <h3 class="card-header-title">Import Data Klien</h3>
                </div>
            </div>
            <div class="card-body flex-1 flex flex-col">
                {{-- Step 1: Download Template --}}
                <div class="mb-6">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Langkah 1: Unduh Template</label>
                    <a href="{{ route('admin.migration.template', 'clients') }}" class="btn btn-secondary w-full justify-center group">
                        <i class="material-icons text-indigo-500 group-hover:text-indigo-600 transition-colors">download</i>
                        Download Template Klien.xlsx
                    </a>
                    <p class="text-[10px] text-slate-400 mt-2 text-center">
                        Format kolom: Nama, Email, No HP, Alamat, PIC.
                    </p>
                </div>

                <hr class="border-dashed border-slate-200 dark:border-slate-700 my-6">

                {{-- Step 2: Upload Form --}}
                <form action="{{ route('admin.migration.import-clients') }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col">
                    @csrf
                    
                    {{-- IMPLEMENTASI KOMPONEN FILE UPLOAD --}}
                    <div class="mb-6">
                        <x-ui.file-upload 
                            name="file"
                            label="Langkah 2: Upload File"
                            accept=".xlsx, .xls"
                            helperText="File Excel (.xlsx atau .xls). Maksimal 10MB."
                            required="true"
                            variant="dropzone"
                        />
                    </div>

                    <div class="mt-auto">
                        <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/20">
                            <i class="material-icons text-[18px]">upload_file</i>
                            Mulai Import Klien
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection