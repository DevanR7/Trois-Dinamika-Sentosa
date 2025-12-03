@extends('admin.layouts.app')

@section('title', 'Tambah Satuan')

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.units.index') }}" class="hover:text-indigo-600 transition-colors">Satuan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Buat Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Satuan Baru</h1>
        </div>
        <a href="{{ route('admin.units.index') }}" 
           class="hidden sm:flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.units.store') }}" method="POST">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <i class="material-icons text-[20px]">straighten</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Formulir Satuan</h3>
            </div>
            
            {{-- Card Body --}}
            <div class="p-6 md:p-8 bg-white">
                @include('admin.units._form')
            </div>

            {{-- Footer Action --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.units.index') }}" 
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