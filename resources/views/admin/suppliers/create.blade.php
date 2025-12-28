@extends('admin.layouts.app')

@section('title', 'Tambah Supplier Baru')

@section('content')
    <div class="max-w-4xl mx-auto">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="page-title">Tambah Supplier</h2>
                <a href="{{ route('admin.suppliers.index') }}" class="flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition-colors mt-1">
                    <i class="material-icons text-base">arrow_back</i> Kembali ke Daftar
                </a>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="card">
            <form action="{{ route('admin.suppliers.store') }}" method="POST" class="p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    {{-- Nama Supplier --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Nama Supplier / PT <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">store</i>
                            </span>
                            <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" 
                                   class="form-input pl-10 @error('supplier_name') border-red-500 @enderror" 
                                   placeholder="Contoh: PT. Sumber Makmur" required>
                        </div>
                        @error('supplier_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- PIC --}}
                    <div>
                        <label class="form-label">Penanggung Jawab (PIC)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">person</i>
                            </span>
                            <input type="text" name="person_in_charge" value="{{ old('person_in_charge') }}" 
                                   class="form-input pl-10" placeholder="Nama kontak...">
                        </div>
                    </div>

                    {{-- No Telepon --}}
                    <div>
                        <label class="form-label">No. Telepon / WA</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">phone</i>
                            </span>
                            <input type="text" name="phone_number" value="{{ old('phone_number') }}" 
                                   class="form-input pl-10" placeholder="0812...">
                        </div>
                    </div>

                    {{-- NPWP --}}
                    <div>
                        <label class="form-label">NPWP</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">badge</i>
                            </span>
                            <input type="text" name="npwp" value="{{ old('npwp') }}" 
                                   class="form-input pl-10" placeholder="Nomor NPWP...">
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Alamat Lengkap</label>
                        <div class="relative">
                            <span class="absolute top-3 left-0 flex items-start pl-3 text-slate-400">
                                <i class="material-icons text-lg">location_on</i>
                            </span>
                            <textarea name="address" rows="2" class="form-input pl-10" placeholder="Alamat gudang / kantor...">{{ old('address') }}</textarea>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2 my-2 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-xs font-bold text-slate-400 uppercase mt-2">Informasi Rekening Bank</p>
                    </div>

                    {{-- Nama Bank --}}
                    <div>
                        <label class="form-label">Nama Bank</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">account_balance</i>
                            </span>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" 
                                   class="form-input pl-10" placeholder="BCA, Mandiri, dll...">
                        </div>
                    </div>

                    {{-- No Rekening --}}
                    <div>
                        <label class="form-label">Nomor Rekening</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">numbers</i>
                            </span>
                            <input type="text" name="account_number" value="{{ old('account_number') }}" 
                                   class="form-input pl-10" placeholder="Nomor rekening...">
                        </div>
                    </div>

                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons text-lg">save</i>
                        Simpan Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection