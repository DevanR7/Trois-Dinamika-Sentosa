@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Supplier</h2>
            <p class="text-sm text-gray-500 mt-1">Perbarui data: <span class="font-bold text-indigo-600">{{ $supplier->supplier_name }}</span></p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
            <i class="bi bi-pencil-square text-indigo-500"></i>
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Supplier</h3>
        </div>
        
        <div class="p-6">
            <form action="{{ route('suppliers.update', $supplier->supplier_id) }}" method="POST">
                @csrf @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Supplier <span class="text-red-500">*</span></label>
                        <input type="text" name="supplier_name" value="{{ old('supplier_name', $supplier->supplier_name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Narahubung (PIC)</label>
                        <input type="text" name="person_in_charge" value="{{ old('person_in_charge', $supplier->person_in_charge) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Telepon</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $supplier->phone_number) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alamat Lengkap</label>
                        <textarea name="address" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('address', $supplier->address) }}</textarea>
                    </div>
                </div>

                <div class="border-t border-dashed border-gray-200 pt-6 mb-6">
                    <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="bi bi-bank text-gray-400"></i> Informasi Bank & Legalitas
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">NPWP</label>
                            <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Bank</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $supplier->bank_name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Rekening</label>
                            <input type="text" name="account_number" value="{{ old('account_number', $supplier->account_number) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                        <i class="bi bi-check-lg mr-2"></i> Update Supplier
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection