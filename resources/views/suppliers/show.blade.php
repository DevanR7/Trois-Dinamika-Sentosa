@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('suppliers.index') }}" class="hover:text-indigo-600 transition-colors">Supplier</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $supplier->supplier_name }}</h1>
        </div>
        
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            
            @if(!$supplier->trashed())
                <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="px-4 py-2 bg-white border border-slate-300 text-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-50 hover:border-indigo-200 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">edit</i> Edit
                </a>
                
                {{-- Global Delete Handler --}}
                <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="delete-form inline-block">
                    @csrf @method('DELETE')
                    <button type="submit" data-name="{{ $supplier->supplier_name }}" class="px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-100 transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">archive</i> Arsipkan
                    </button>
                </form>
            @else
                <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="inline-block" onsubmit="return confirm('Pulihkan supplier ini?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-bold hover:bg-emerald-100 transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">restore</i> Pulihkan
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KIRI: INFO DETAIL --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="material-icons text-[20px]">badge</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Profil Lengkap</h3>
                </div>
                
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1.5 tracking-wide">Narahubung (PIC)</span>
                        <p class="text-sm font-semibold text-slate-800">{{ $supplier->person_in_charge ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1.5 tracking-wide">Nomor Telepon</span>
                        <p class="text-sm font-semibold text-slate-800">{{ $supplier->phone_number ?? '-' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1.5 tracking-wide">Alamat</span>
                        <p class="text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed">
                            {{ $supplier->address ?? 'Tidak ada alamat tercatat.' }}
                        </p>
                    </div>
                    
                    <div class="sm:col-span-2 pt-4 border-t border-dashed border-slate-200 mt-2">
                        <h4 class="text-xs font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-base">account_balance</i> Data Perbankan
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase">NPWP</span>
                                <span class="text-sm text-slate-800 font-mono block mt-1">{{ $supplier->npwp ?? '-' }}</span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase">Bank</span>
                                <span class="text-sm text-slate-800 block mt-1">{{ $supplier->bank_name ?? '-' }}</span>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase">No. Rekening</span>
                                <span class="text-sm text-slate-800 font-mono font-bold block mt-1">{{ $supplier->account_number ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KANAN: SALDO & META --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Card Saldo --}}
            <div class="dashboard-card p-6 text-center shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Deposit</span>
                <div class="text-3xl font-bold {{ $supplier->balance > 0 ? 'text-indigo-600' : 'text-slate-800' }} my-3 tracking-tight">
                    Rp {{ number_format($supplier->balance ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-400">Saldo mengendap saat ini.</p>
                
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border {{ $supplier->trashed() ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                        <i class="material-icons text-[14px] mr-1.5">{{ $supplier->trashed() ? 'archive' : 'check_circle' }}</i> 
                        {{ $supplier->trashed() ? 'DIARSIPKAN' : 'AKTIF' }}
                    </span>
                </div>
            </div>

            {{-- Card Meta --}}
             <div class="dashboard-card p-6 shadow-sm">
                <h3 class="text-xs font-bold text-slate-800 uppercase mb-4 tracking-wide border-b border-slate-100 pb-2">Informasi Dokumen</h3>
                <div class="space-y-4 text-xs text-slate-600">
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-2 text-slate-500"><i class="material-icons text-[16px]">event</i> Dibuat</span>
                        <span class="font-bold bg-slate-100 px-2 py-1 rounded">{{ optional($supplier->created_at)->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-2 text-slate-500"><i class="material-icons text-[16px]">update</i> Diperbarui</span>
                        <span class="font-bold bg-slate-100 px-2 py-1 rounded">{{ optional($supplier->updated_at)->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection