@extends('layouts.app')

@section('title', 'Edit Tarif Pajak')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('taxes.index') }}" class="hover:text-indigo-600 transition">Pajak</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Tarif Pajak</h2>
            <p class="text-sm text-gray-500 mt-1">
                Perbarui data untuk: <span class="font-bold text-indigo-600">{{ $tax->name }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('taxes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-lg mt-0.5">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('taxes.update', $tax->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">edit_note</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Tarif</h3>
            </div>
            
            <div class="p-6 space-y-6">
                
                {{-- Nama Pajak --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Pajak <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $tax->name) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                </div>

                {{-- Tarif --}}
                <div>
                    <label for="rate" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tarif (%) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm w-1/2">
                        <input type="number" step="0.01" name="rate" id="rate" value="{{ old('rate', $tax->rate) }}" class="form-input block w-full rounded-lg border-gray-300 pr-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-bold text-right" required>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                </div>

                {{-- Checkbox Aktif --}}
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old("is_active", $tax->is_active) ? "checked" : "" }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700 cursor-pointer">Status Aktif</label>
                        <p class="text-gray-500 text-xs">Jika tidak dicentang, pajak ini tidak akan muncul di pilihan transaksi.</p>
                    </div>
                </div>

            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('taxes.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Update Tarif
                </button>
            </div>
        </div>
    </form>
</div>
@endsection