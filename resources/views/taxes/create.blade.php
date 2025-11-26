@extends('layouts.app')

@section('title', 'Tambah Tarif Pajak')

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('taxes.index') }}" class="hover:text-indigo-600 transition-colors">Pajak</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Buat Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Tarif Pajak</h1>
        </div>
        <a href="{{ route('taxes.index') }}" 
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

    <form action="{{ route('taxes.store') }}" method="POST">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <i class="material-icons text-[20px]">percent</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Formulir Pajak</h3>
            </div>
            
            <div class="p-6 md:p-8 bg-white space-y-6">
                
                {{-- Nama Pajak --}}
                <div>
                    <label for="name">Nama Pajak <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="form-input" 
                           placeholder="Contoh: PPN, PPh 23" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Tarif --}}
                <div>
                    <label for="rate">Tarif (%) <span class="text-red-500">*</span></label>
                    <div class="relative w-full sm:w-1/2">
                        <input type="number" step="0.01" name="rate" id="rate" value="{{ old('rate') }}" 
                               class="form-input pr-10 font-bold text-right text-indigo-600" 
                               placeholder="11.00" required>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-slate-400 font-bold text-sm">%</span>
                        </div>
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">Gunakan titik untuk desimal (contoh: 0.5 untuk 0,5%).</p>
                    @error('rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Checkbox Aktif --}}
                <div class="pt-4 border-t border-slate-100">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <div class="relative flex items-center pt-0.5">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked 
                                   class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-slate-300 transition-all checked:border-indigo-600 checked:bg-indigo-600 focus:ring-2 focus:ring-indigo-100">
                            <i class="material-icons absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[16px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none">check</i>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">Status Aktif</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Pajak ini akan muncul saat membuat invoice/transaksi.</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('taxes.index') }}" 
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