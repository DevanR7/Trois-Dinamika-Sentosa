@extends('layouts.app')

@section('title', 'Edit Metode Pembayaran')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('payment-methods.index') }}" class="hover:text-indigo-600 transition">Metode</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Metode</h2>
            <p class="text-sm text-gray-500 mt-1">
                Perbarui konfigurasi: <span class="font-bold text-indigo-600">{{ $paymentMethod->name }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('payment-methods.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('payment-methods.update', $paymentMethod->payment_method_id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">edit_note</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Metode</h3>
            </div>
            
            <div class="p-6 space-y-6">
                
                {{-- Nama Metode --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Metode <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $paymentMethod->name) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                </div>

                {{-- Tipe Proses --}}
                <div>
                    <label for="type" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Proses <span class="text-red-500">*</span></label>
                    <select name="type" id="type" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="direct" @selected(old('type', $paymentMethod->type) == 'direct')>Direct (Langsung Masuk Kas/Bank)</option>
                        <option value="pending" @selected(old('type', $paymentMethod->type) == 'pending')>Pending (Butuh Kliring - Cek/Giro)</option>
                        <option value="gateway" @selected(old('type', $paymentMethod->type) == 'gateway')>Payment Gateway (Otomatis)</option>
                    </select>
                </div>

                {{-- Konfigurasi Wajib Isi --}}
                <div>
                    <label for="required_fields_config" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Wajib Isi (Pelanggan) <span class="text-red-500">*</span></label>
                    <select name="required_fields_config" id="required_fields_config" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="none" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'none')>Tidak Ada</option>
                        <option value="proof_only" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'proof_only')>Wajib Bukti Foto</option>
                        <option value="reference_only" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'reference_only')>Wajib No. Referensi</option>
                        <option value="proof_and_reference" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'proof_and_reference')>Wajib Bukti & Referensi</option>
                    </select>
                </div>

                {{-- Status Switch --}}
                <div class="flex items-start pt-2">
                    <div class="flex items-center h-5">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $paymentMethod->is_active)) class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700 cursor-pointer">Status Aktif</label>
                        <p class="text-gray-500 text-xs">Aktifkan metode ini agar muncul di pilihan pembayaran.</p>
                    </div>
                </div>

            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('payment-methods.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Update Metode
                </button>
            </div>
        </div>
    </form>
</div>
@endsection