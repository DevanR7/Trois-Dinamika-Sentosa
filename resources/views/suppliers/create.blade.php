@extends('layouts.app')

@section('title', 'Tambah Supplier')

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Tambah Supplier</h2>
            <p class="text-sm text-gray-500 mt-1">Masukkan data vendor atau pemasok baru.</p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Terdapat kesalahan pada inputan:</h3>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
            <i class="bi bi-person-plus-fill text-indigo-500"></i>
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Supplier</h3>
        </div>
        
        <div class="p-6">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    {{-- INFO DASAR --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Supplier <span class="text-red-500">*</span></label>
                        <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="PT. Contoh Sejahtera" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Narahubung (PIC)</label>
                        <input type="text" name="person_in_charge" value="{{ old('person_in_charge') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Bpk. Budi">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Telepon</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="0812...">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alamat Lengkap</label>
                        <textarea name="address" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Jalan Raya..."></textarea>
                    </div>
                </div>

                {{-- INFO BANK --}}
                <div class="border-t border-dashed border-gray-200 pt-6 mb-6">
                    <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="bi bi-bank text-gray-400"></i> Informasi Bank & Legalitas (Opsional)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">NPWP</label>
                            <input type="text" name="npwp" value="{{ old('npwp') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Bank</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="BCA / Mandiri">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Rekening</label>
                            <input type="text" name="account_number" value="{{ old('account_number') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                        <i class="bi bi-check-circle mr-2"></i> Simpan Supplier
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection