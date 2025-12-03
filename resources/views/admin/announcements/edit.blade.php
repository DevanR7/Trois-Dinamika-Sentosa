@extends('admin.layouts.app')

@section('title', 'Edit Pengumuman')

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.announcements.index') }}" class="hover:text-indigo-600 transition-colors font-medium">Pengumuman</a>
                <i class="material-icons text-[14px] text-slate-400">chevron_right</i>
                <span class="text-slate-800 font-semibold">Edit</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Pengumuman</h1>
            <p class="text-slate-500 text-sm mt-1">Perbarui konten atau target audiens pengumuman.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('admin.announcements.index') }}" 
               class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 shadow-sm transition-all flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">edit_note</i>
                </div>
                <h3 class="card-title mb-0">Edit Data</h3>
            </div>
            
            <div class="p-6">
                @include('admin.announcements._form', [
                    'announcement' => $announcement, 
                    'clients' => $clients, 
                    'selectedClientIds' => $selectedClientIds
                ])
            </div>

            <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('admin.announcements.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 shadow-sm transition-all flex items-center justify-center">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">save</i> Update Pengumuman
                </button>
            </div>
        </div>
    </form>
</div>
@endsection