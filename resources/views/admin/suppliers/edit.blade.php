@extends('admin.layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="max-w-5xl mx-auto animate-enter">
    <form action="{{ route('admin.suppliers.update', $supplier->supplier_id) }}" method="POST">
        @csrf
        @method('PUT')
        
        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="material-icons text-indigo-500">edit_note</i> Edit Supplier
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Perbarui data untuk <span class="font-bold text-slate-700 dark:text-slate-300">{{ $supplier->supplier_name }}</span>.
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary shadow-lg shadow-indigo-500/30">
                    <i class="material-icons text-sm">save</i> Simpan Perubahan
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: IDENTITAS & KONTAK (2/3 Lebar) --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="card p-6">
                    <h3 class="section-title flex items-center gap-2 mb-4">
                        <i class="material-icons text-slate-400 text-sm">badge</i> Identitas & Kontak
                    </h3>
                    
                    <div class="space-y-5">
                        {{-- Nama Supplier --}}
                        <div>
                            <label class="form-label label-required">Nama Supplier / Perusahaan</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-lg">store</i>
                                </div>
                                <input type="text" name="supplier_name" 
                                       class="form-input pl-10 @error('supplier_name') is-invalid @enderror" 
                                       value="{{ old('supplier_name', $supplier->supplier_name) }}" 
                                       required>
                            </div>
                            @error('supplier_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Grid: PIC & Phone --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="form-label">Person In Charge (PIC)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="material-icons text-slate-400 text-lg">person</i>
                                    </div>
                                    <input type="text" name="person_in_charge" 
                                           class="form-input pl-10" 
                                           value="{{ old('person_in_charge', $supplier->person_in_charge) }}" 
                                           placeholder="Nama Sales / Kontak">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">No. Telepon / WhatsApp</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="material-icons text-slate-400 text-lg">call</i>
                                    </div>
                                    <input type="text" name="phone_number" 
                                           class="form-input pl-10" 
                                           value="{{ old('phone_number', $supplier->phone_number) }}" 
                                           placeholder="0812xxxx">
                                </div>
                            </div>
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label class="form-label">Alamat Lengkap</label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 pointer-events-none">
                                    <i class="material-icons text-slate-400 text-lg">place</i>
                                </div>
                                <textarea name="address" class="form-textarea pl-10" rows="3">{{ old('address', $supplier->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KEUANGAN & LEGAL (1/3 Lebar) --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Card Keuangan --}}
                <div class="card p-6 h-full border-t-4 border-t-indigo-500">
                    <h3 class="section-title flex items-center gap-2 mb-4">
                        <i class="material-icons text-slate-400 text-sm">account_balance_wallet</i> Info Pembayaran
                    </h3>
                    
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg mb-5 flex gap-3 items-start">
                        <i class="material-icons text-indigo-500 text-lg mt-0.5">info</i>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300 leading-snug">
                            Pastikan info bank akurat untuk keperluan bukti transfer.
                        </p>
                    </div>

                    <div class="space-y-5">
                        {{-- Nama Bank --}}
                        <div>
                            <label class="form-label">Nama Bank</label>
                            <div class="relative">
                                <select name="bank_name" class="tom-select">
                                    <option value="">Pilih Bank</option>
                                    @foreach(['BCA', 'MANDIRI', 'BRI', 'BNI', 'CIMB', 'BSI', 'DANAMON', 'PERMATA', 'LAINNYA'] as $bank)
                                        <option value="{{ $bank }}" {{ old('bank_name', $supplier->bank_name) == $bank ? 'selected' : '' }}>
                                            {{ $bank }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- No Rekening --}}
                        <div>
                            <label class="form-label">Nomor Rekening</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-lg">credit_card</i>
                                </div>
                                <input type="text" name="account_number" 
                                       class="form-input pl-10 font-mono tracking-wide" 
                                       value="{{ old('account_number', $supplier->account_number) }}">
                            </div>
                        </div>

                        <hr class="border-slate-100 dark:border-slate-700">

                        {{-- NPWP --}}
                        <div>
                            <label class="form-label">NPWP (Opsional)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-lg">assignment</i>
                                </div>
                                <input type="text" name="npwp" 
                                       class="form-input pl-10" 
                                       value="{{ old('npwp', $supplier->npwp) }}"
                                       placeholder="00.000.000.0-000.000">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Init TomSelect dengan konfigurasi sama seperti Create
        document.querySelectorAll('.tom-select').forEach(el => {
            new TomSelect(el, {
                create: true, 
                sortField: {field: "text", direction: "asc"},
                placeholder: 'Pilih atau ketik bank...',
                controlClass: 'ts-control !min-h-[42px] !h-[42px] !rounded-xl !border-slate-300 dark:!border-slate-600 !pl-4'
            });
        });
    });
</script>
@endpush