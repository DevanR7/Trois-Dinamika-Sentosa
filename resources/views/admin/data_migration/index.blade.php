@extends('admin.layouts.app')

@section('title', 'Migrasi Data')

@section('content')

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Migrasi Data</h1>
            <p class="page-subtitle">Import data massal (Produk & Klien) menggunakan file Excel.</p>
        </div>
    </div>

    {{-- WARNING ALERT --}}
    <div class="mb-8 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl shadow-sm dark:bg-amber-900/10 dark:border-amber-600">
        <div class="flex items-start gap-3">
            <i class="material-icons text-amber-600 dark:text-amber-500 mt-0.5">warning</i>
            <div class="text-sm text-amber-800 dark:text-amber-400 leading-relaxed">
                <strong>Perhatian:</strong>
                <ul class="list-disc ml-4 mt-1 space-y-1">
                    <li>Pastikan format file Excel sesuai dengan template yang disediakan.</li>
                    <li>Data yang diimport akan ditambahkan ke database (tidak menimpa data lama kecuali kode/ID sama).</li>
                    <li>Disarankan untuk melakukan <strong>Backup Database</strong> sebelum melakukan import massal.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- CARD 1: IMPORT PRODUK --}}
        <div class="card h-full flex flex-col">
            <div class="card-header bg-indigo-50/50 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center dark:bg-indigo-900/30 dark:text-indigo-400">
                        <i class="material-icons">inventory_2</i>
                    </div>
                    <div>
                        <h3 class="card-header-title text-indigo-900 dark:text-indigo-300">Import Produk</h3>
                        <p class="text-xs text-slate-500">Stok, Harga Beli, Harga Jual</p>
                    </div>
                </div>
            </div>
            <div class="card-body flex-1 flex flex-col justify-between">
                
                <div class="mb-6">
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                        Unduh template Excel di bawah ini untuk memastikan kolom data sesuai dengan sistem.
                    </p>
                    {{-- Pastikan Anda membuat route untuk download template atau taruh file di public --}}
                    <a href="{{ route('admin.migration.template', 'products') }}" class="btn btn-sm btn-secondary w-full justify-center">
                        <i class="material-icons text-sm mr-2">download</i> Download Template Produk
                    </a>
                </div>

                <form action="{{ route('admin.migration.import-products') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    {{-- Upload Area --}}
                    <div class="upload-area" id="uploadProduct">
                        <input type="file" name="file" id="fileProduct" 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                               accept=".xlsx, .xls" required>
                        
                        <div class="text-center transition-all duration-300" id="placeholderProduct">
                            <i class="material-icons text-4xl text-slate-300 mb-2">upload_file</i>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Klik atau seret file Excel</p>
                            <p class="text-[10px] text-slate-400 mt-1">Format: .xlsx, .xls</p>
                        </div>

                        {{-- File Info (Hidden by default) --}}
                        <div class="text-center hidden" id="infoProduct">
                            <i class="material-icons text-4xl text-emerald-500 mb-2">description</i>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 break-all" id="filenameProduct"></p>
                            <p class="text-[10px] text-indigo-500 mt-1 cursor-pointer hover:underline">Ganti File</p>
                        </div>
                    </div>
                    @error('file') <div class="invalid-feedback text-center">{{ $message }}</div> @enderror

                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <i class="material-icons text-sm mr-2">publish</i> Upload Data Produk
                    </button>
                </form>

            </div>
        </div>

        {{-- CARD 2: IMPORT KLIEN --}}
        <div class="card h-full flex flex-col">
            <div class="card-header bg-emerald-50/50 dark:bg-emerald-900/10 border-b border-emerald-100 dark:border-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center dark:bg-emerald-900/30 dark:text-emerald-400">
                        <i class="material-icons">groups</i>
                    </div>
                    <div>
                        <h3 class="card-header-title text-emerald-900 dark:text-emerald-300">Import Klien</h3>
                        <p class="text-xs text-slate-500">Data Pelanggan & Kontak</p>
                    </div>
                </div>
            </div>
            <div class="card-body flex-1 flex flex-col justify-between">
                
                <div class="mb-6">
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                        Gunakan template ini untuk mendaftarkan banyak pelanggan sekaligus.
                    </p>
                    <a href="{{ route('admin.migration.template', 'clients') }}" class="btn btn-sm btn-secondary w-full justify-center">
                        <i class="material-icons text-sm mr-2">download</i> Download Template Klien
                    </a>
                </div>

                <form action="{{ route('admin.migration.import-clients') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    {{-- Upload Area --}}
                    <div class="upload-area" id="uploadClient">
                        <input type="file" name="file" id="fileClient" 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                               accept=".xlsx, .xls" required>
                        
                        <div class="text-center transition-all duration-300" id="placeholderClient">
                            <i class="material-icons text-4xl text-slate-300 mb-2">upload_file</i>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Klik atau seret file Excel</p>
                            <p class="text-[10px] text-slate-400 mt-1">Format: .xlsx, .xls</p>
                        </div>

                        <div class="text-center hidden" id="infoClient">
                            <i class="material-icons text-4xl text-emerald-500 mb-2">description</i>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 break-all" id="filenameClient"></p>
                            <p class="text-[10px] text-indigo-500 mt-1 cursor-pointer hover:underline">Ganti File</p>
                        </div>
                    </div>
                    @error('file') <div class="invalid-feedback text-center">{{ $message }}</div> @enderror

                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <i class="material-icons text-sm mr-2">publish</i> Upload Data Klien
                    </button>
                </form>

            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Fungsi reusable untuk handling UI file upload
        function handleFileUpload(inputId, placeholderId, infoId, nameId, containerId) {
            const input = document.getElementById(inputId);
            const placeholder = document.getElementById(placeholderId);
            const info = document.getElementById(infoId);
            const nameDisplay = document.getElementById(nameId);
            const container = document.getElementById(containerId);

            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    nameDisplay.textContent = file.name;
                    
                    // Toggle UI
                    placeholder.classList.add('hidden');
                    info.classList.remove('hidden');
                    container.classList.add('border-emerald-500', 'bg-emerald-50/50', 'dark:bg-emerald-900/20');
                } else {
                    // Reset
                    placeholder.classList.remove('hidden');
                    info.classList.add('hidden');
                    container.classList.remove('border-emerald-500', 'bg-emerald-50/50', 'dark:bg-emerald-900/20');
                }
            });
        }

        // Init untuk Produk
        handleFileUpload('fileProduct', 'placeholderProduct', 'infoProduct', 'filenameProduct', 'uploadProduct');
        
        // Init untuk Klien
        handleFileUpload('fileClient', 'placeholderClient', 'infoClient', 'filenameClient', 'uploadClient');

        // Loading state pada form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.classList.add('is-loading');
                btn.disabled = true;
            });
        });
    });
</script>
@endpush