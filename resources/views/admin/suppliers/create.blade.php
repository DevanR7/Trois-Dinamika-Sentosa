@extends('admin.layouts.app')

@section('title', 'Tambah Supplier')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.suppliers.index') }}" class="hover:text-indigo-600 transition-colors">Supplier</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Input Data</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Supplier Baru</h1>
        </div>
        <a href="{{ route('admin.suppliers.index') }}" 
           class="hidden sm:flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-enter">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 text-red-500 mt-0.5">
                    <i class="material-icons text-xl">error_outline</i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.suppliers.store') }}" method="POST">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <i class="material-icons text-[20px]">domain_add</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Supplier</h3>
            </div>
            
            <div class="p-6 md:p-8 bg-white space-y-8">
                
                {{-- INFO DASAR --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="supplier_name">Nama Supplier <span class="text-red-500">*</span></label>
                        <input type="text" name="supplier_name" id="supplier_name" value="{{ old('supplier_name') }}" 
                               class="form-input" placeholder="PT. Contoh Sejahtera" required>
                        @error('supplier_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="person_in_charge">Narahubung (PIC)</label>
                        <input type="text" name="person_in_charge" id="person_in_charge" value="{{ old('person_in_charge') }}" 
                               class="form-input" placeholder="Nama PIC">
                        @error('person_in_charge') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone_number">No. Telepon</label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" 
                               class="form-input" placeholder="0812...">
                        @error('phone_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="address">Alamat Lengkap</label>
                        <textarea name="address" id="address" rows="2" class="form-textarea" placeholder="Alamat lengkap perusahaan...">{{ old('address') }}</textarea>
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- INFO BANK --}}
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-slate-200 pb-2">
                        <i class="material-icons text-slate-400 text-base">account_balance</i> Informasi Bank & Legalitas
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="npwp">NPWP</label>
                            <input type="text" name="npwp" id="npwp" value="{{ old('npwp') }}" class="form-input" placeholder="Nomor NPWP">
                            @error('npwp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bank_name">Nama Bank</label>
                            <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" class="form-input" placeholder="BCA / Mandiri">
                            @error('bank_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="account_number">No. Rekening</label>
                            <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" class="form-input" placeholder="Nomor Rekening">
                            @error('account_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.suppliers.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">save</i> Simpan Data
                </button>
            </div>
        </div>
    </form>
</div>
@endsection