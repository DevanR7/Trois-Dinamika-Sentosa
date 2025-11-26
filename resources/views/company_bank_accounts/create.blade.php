@extends('layouts.app')

@section('title', 'Tambah Akun Bank Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('company-bank-accounts.index') }}" class="hover:text-indigo-600 transition-colors">Akun Bank</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Akun Bank</h1>
        </div>
        <a href="{{ route('company-bank-accounts.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
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

    <form action="{{ route('company-bank-accounts.store') }}" method="POST" id="bank-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">account_balance</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Rekening</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Nama Bank --}}
                <div>
                    <label for="bank_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Bank <span class="text-red-500">*</span></label>
                    <input type="text" class="form-input" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" placeholder="Contoh: BCA, Mandiri, Kas Kecil" required>
                    @error('bank_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Atas Nama --}}
                <div>
                    <label for="account_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Atas Nama <span class="text-red-500">*</span></label>
                    <input type="text" class="form-input" id="account_name" name="account_name" value="{{ old('account_name') }}" placeholder="Contoh: PT. Maju Jaya" required>
                    @error('account_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- No Rekening --}}
                <div>
                    <label for="account_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Rekening</label>
                    <input type="text" class="form-input font-mono" id="account_number" name="account_number" value="{{ old('account_number') }}" placeholder="1234567890">
                    @error('account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Hubungkan ke COA --}}
                <div>
                    <label for="chart_of_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Hubungkan ke Akun (COA) <span class="text-red-500">*</span></label>
                    <select class="form-input select2-basic" id="chart_of_account_id" name="chart_of_account_id" required>
                        <option value="" disabled selected>-- Pilih Akun Aset --</option>
                        @foreach ($assetAccounts as $asset)
                            <option value="{{ $asset->account_id }}" @selected(old('chart_of_account_id') == $asset->account_id)>
                                {{ $asset->account_number }} - {{ $asset->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-slate-400 flex items-center gap-1"><i class="material-icons text-[12px]">link</i> Akun ini akan digunakan untuk penjurnalan otomatis.</p>
                    @error('chart_of_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Switch Active --}}
                <div class="md:col-span-2 pt-4 border-t border-slate-100">
                    <label class="flex items-center cursor-pointer group w-fit">
                        <div class="relative">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </div>
                        <div class="ml-3">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition-colors block">Status Aktif</span>
                            <span class="text-[11px] text-slate-400 block">Akun aktif dapat digunakan untuk transaksi.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('company-bank-accounts.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan Akun
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-basic').select2({ placeholder: '-- Pilih Akun Aset --', allowClear: false, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush