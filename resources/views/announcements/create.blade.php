@extends('layouts.app')

@section('title', 'Buat Pengumuman Baru')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('announcements.index') }}" class="hover:text-indigo-600 transition">Pengumuman</a>
                <span>/</span>
                <span class="text-gray-800">Baru</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Pengumuman</h2>
            <p class="text-sm text-gray-500 mt-1">Buat informasi baru untuk disiarkan ke klien.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('announcements.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('announcements.store') }}" method="POST">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">campaign</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Pengumuman</h3>
            </div>
            
            <div class="p-6">
                @include('announcements._form', ['clients' => $clients])
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('announcements.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">send</i> Simpan & Terbitkan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection