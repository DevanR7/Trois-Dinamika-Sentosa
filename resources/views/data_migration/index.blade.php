@extends('layouts.app')

@section('title', 'Migrasi Data')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Migrasi Data (Import Excel)</h3>
            <p class="text-sm text-gray-500 mt-1">Upload file Excel untuk mengimpor data massal ke dalam sistem.</p>
        </div>
        {{-- Bisa tambah tombol download template jika ada --}}
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-green-500">check_circle</i>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500">error</i>
            <span class="text-sm text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- KARTU 1: IMPORT PRODUK --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600">
                        <i class="material-icons text-2xl">inventory_2</i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-900">Import Produk</h4>
                        <p class="text-xs text-gray-500">Stok & Inventaris</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-3 mb-6 border border-gray-100">
                    <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Format Header Wajib:</p>
                    <code class="text-[11px] text-indigo-600 font-mono break-words leading-relaxed">
                        kode_produk, nama_produk, harga_beli, harga_jual, stok_awal, satuan, nama_supplier, deskripsi
                    </code>
                </div>

                <form action="{{ route('migration.import-products') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel (.xlsx)</label>
                        <input type="file" name="file" required 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer border border-gray-300 rounded-lg bg-white">
                    </div>
                    
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                        <i class="material-icons text-lg mr-2">upload_file</i> Upload & Import Produk
                    </button>
                </form>
            </div>
        </div>

        {{-- KARTU 2: IMPORT KLIEN --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-teal-50 rounded-full flex items-center justify-center text-teal-600">
                        <i class="material-icons text-2xl">groups</i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-900">Import Klien</h4>
                        <p class="text-xs text-gray-500">Data Pelanggan</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-3 mb-6 border border-gray-100">
                    <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Format Header Wajib:</p>
                    <code class="text-[11px] text-teal-600 font-mono break-words leading-relaxed">
                        nama_klien, email, no_telepon, alamat, pic
                    </code>
                </div>

                <form action="{{ route('migration.import-clients') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel (.xlsx)</label>
                        <input type="file" name="file" required 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 transition cursor-pointer border border-gray-300 rounded-lg bg-white">
                    </div>
                    
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-teal-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 shadow-md transition">
                        <i class="material-icons text-lg mr-2">upload_file</i> Upload & Import Klien
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection