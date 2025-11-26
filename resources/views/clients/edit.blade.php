@extends('layouts.app')

@section('title', 'Edit Klien')

@section('content')
<div class="max-w-5xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('clients.index') }}" class="hover:text-indigo-600 transition-colors">Klien</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Klien</h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            @if(!$client->trashed())
                {{-- Global Delete Handler --}}
                <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-confirm hidden sm:block">
                    @csrf @method('DELETE')
                    <button type="submit" 
                            data-title="Arsipkan Klien?" 
                            data-text="Klien <b>{{ $client->client_name }}</b> akan diarsipkan." 
                            data-btn-text="Ya, Arsipkan" 
                            data-btn-color="#ef4444"
                            class="h-[48px] px-5 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                        <i class="material-icons text-[18px]">archive</i> Arsipkan
                    </button>
                </form>
            @endif
            
            <a href="{{ route('clients.index') }}" 
               class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('clients.update', $client->client_id) }}" method="POST">
        @csrf @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <i class="material-icons text-[20px]">edit_note</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Informasi</h3>
            </div>
            
            <div class="p-6 md:p-8 bg-white">
                @include('clients._form')
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('clients.index') }}" 
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush