@extends('layouts.app')

@section('title', 'Migrasi Data')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Migrasi Data (Import Excel)</h1>
            <p class="text-slate-500 text-sm mt-1">Upload file Excel untuk mengimpor data massal ke dalam sistem.</p>
        </div>
        {{-- Opsional: Tombol Download Template --}}
        {{-- <a href="#" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">download</i> Download Template
        </a> --}}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        {{-- KARTU 1: IMPORT PRODUK --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 flex flex-col h-full">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 flex-shrink-0">
                    <i class="material-icons text-[24px]">inventory_2</i>
                </div>
                <div>
                    <h4 class="text-base font-bold text-slate-800">Import Produk</h4>
                    <p class="text-xs text-slate-500">Upload data stok & inventaris baru.</p>
                </div>
            </div>
            
            <div class="p-6 flex-grow flex flex-col justify-between">
                <div>
                    <div class="bg-indigo-50 rounded-lg p-4 mb-6 border border-indigo-100">
                        <p class="text-[10px] font-bold text-indigo-400 uppercase mb-2 tracking-wider">Kolom Excel Wajib:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach(['kode_produk', 'nama_produk', 'harga_beli', 'harga_jual', 'stok_awal', 'satuan', 'nama_supplier', 'deskripsi'] as $col)
                                <span class="inline-block px-2 py-1 bg-white text-indigo-700 text-[10px] font-mono rounded border border-indigo-200">{{ $col }}</span>
                            @endforeach
                        </div>
                    </div>

                    <form action="{{ route('migration.import-products') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">File Excel (.xlsx)</label>
                            <input type="file" name="file" required 
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer bg-slate-50 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                                   accept=".xlsx, .xls">
                        </div>
                        
                        <button type="submit" class="w-full h-[48px] bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">upload_file</i> 
                            Mulai Import Produk
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KARTU 2: IMPORT KLIEN --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 flex flex-col h-full">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 flex-shrink-0">
                    <i class="material-icons text-[24px]">groups</i>
                </div>
                <div>
                    <h4 class="text-base font-bold text-slate-800">Import Klien</h4>
                    <p class="text-xs text-slate-500">Upload data pelanggan massal.</p>
                </div>
            </div>
            
            <div class="p-6 flex-grow flex flex-col justify-between">
                <div>
                    <div class="bg-emerald-50 rounded-lg p-4 mb-6 border border-emerald-100">
                        <p class="text-[10px] font-bold text-emerald-500 uppercase mb-2 tracking-wider">Kolom Excel Wajib:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach(['nama_klien', 'email', 'no_telepon', 'alamat', 'pic'] as $col)
                                <span class="inline-block px-2 py-1 bg-white text-emerald-700 text-[10px] font-mono rounded border border-emerald-200">{{ $col }}</span>
                            @endforeach
                        </div>
                    </div>

                    <form action="{{ route('migration.import-clients') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">File Excel (.xlsx)</label>
                            <input type="file" name="file" required 
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer bg-slate-50 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                                   accept=".xlsx, .xls">
                        </div>
                        
                        <button type="submit" class="w-full h-[48px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">upload_file</i> 
                            Mulai Import Klien
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
        
        // Handle Validation Errors from Server
        @if ($errors->any())
            @foreach($errors->all() as $error)
                window.showToast("{{ $error }}", 'error');
            @endforeach
        @endif
    });
</script>
@endpush