@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('roles.index') }}" class="hover:text-indigo-600 transition">Roles</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Role</h2>
            <p class="text-sm text-gray-500 mt-1">
                Perbarui hak akses untuk: <span class="font-bold text-indigo-600">{{ Str::title($role->name) }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('roles.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">admin_panel_settings</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Konfigurasi Role</h3>
            </div>
            
            <div class="p-6">
                @include('roles._form')
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('roles.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">save</i> Update Role
                </button>
            </div>
        </div>
    </form>
</div>
@endsection