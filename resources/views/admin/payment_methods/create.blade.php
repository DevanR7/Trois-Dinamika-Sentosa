@extends('admin.layouts.app')

@section('title', 'Tambah Metode Pembayaran')

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.payment-methods.index') }}" class="hover:text-indigo-600 transition-colors">Metode</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Metode</h1>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.payment-methods.store') }}" method="POST">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">payments</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Form Data Metode</h3>
            </div>
            
            <div class="p-6 space-y-6">
                
                {{-- Nama Metode --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Metode <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-input" placeholder="Contoh: Transfer BCA, Giro Mundur" required>
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tipe Proses --}}
                <div>
                    <label for="type" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipe Proses <span class="text-red-500">*</span></label>
                    <select name="type" id="type" class="form-input select2-basic" required>
                        <option value="direct" @selected(old('type') == 'direct')>Direct (Langsung Masuk Kas/Bank)</option>
                        <option value="pending" @selected(old('type') == 'pending')>Pending (Butuh Kliring - Cek/Giro)</option>
                        <option value="gateway" @selected(old('type') == 'gateway')>Payment Gateway (Otomatis)</option>
                    </select>
                    <p class="mt-1.5 text-[11px] text-slate-400 flex items-center gap-1">
                        <i class="material-icons text-[12px]">info</i> Pilih "Pending" jika pembayaran butuh proses pencairan (seperti Cek/Giro).
                    </p>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Konfigurasi Wajib Isi --}}
                <div>
                    <label for="required_fields_config" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Data Wajib Isi (Pelanggan) <span class="text-red-500">*</span></label>
                    <select name="required_fields_config" id="required_fields_config" class="form-input select2-basic" required>
                        <option value="none" @selected(old('required_fields_config', 'none') == 'none')>Tidak Ada (Langsung Nominal)</option>
                        <option value="proof_only" @selected(old('required_fields_config') == 'proof_only')>Wajib Upload Bukti Foto</option>
                        <option value="reference_only" @selected(old('required_fields_config') == 'reference_only')>Wajib Isi No. Referensi</option>
                        <option value="proof_and_reference" @selected(old('required_fields_config') == 'proof_and_reference')>Wajib Bukti & Referensi</option>
                    </select>
                    @error('required_fields_config') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Status Switch --}}
                <div class="pt-4 border-t border-slate-100">
                    <label class="flex items-center cursor-pointer group w-fit">
                        <div class="relative">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </div>
                        <div class="ml-3">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition-colors block">Status Aktif</span>
                            <span class="text-[11px] text-slate-400 block">Aktifkan metode ini agar muncul di pilihan.</span>
                        </div>
                    </label>
                </div>

            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('admin.payment-methods.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush