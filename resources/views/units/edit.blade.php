@extends('layouts.app')

@section('title', 'Edit Satuan')

@section('content')
<div class="max-w-2xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('units.index') }}" class="hover:text-indigo-600 transition-colors">Satuan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Satuan</h1>
        </div>
        <a href="{{ route('units.index') }}" 
           class="hidden sm:flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
        </a>
    </div>
    
    <form action="{{ route('units.update', $unit->unit_id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Banner Info --}}
            <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-200 text-indigo-700 flex items-center justify-center">
                    <i class="material-icons text-[20px]">edit</i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-indigo-800 uppercase tracking-wide">Mengedit Data</h3>
                    <p class="text-xs text-indigo-600">Satuan saat ini: <span class="font-bold">{{ $unit->name }}</span></p>
                </div>
            </div>
            
            {{-- Card Body --}}
            <div class="p-6 md:p-8 bg-white">
                @include('units._form')
            </div>

            {{-- Footer Action --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('units.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">check_circle</i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection