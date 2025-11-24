@extends('layouts.app')

@section('title', 'Edit Akun Bank')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('company-bank-accounts.index') }}" class="hover:text-indigo-600 transition">Akun Bank</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Akun Bank</h2>
            <p class="text-sm text-gray-500 mt-1">
                Perbarui data akun: <span class="font-bold text-indigo-600">{{ $account->bank_name }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('company-bank-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('company-bank-accounts.update', $account) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">edit_note</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Akun</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Nama Bank --}}
                <div>
                    <label for="bank_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Bank <span class="text-red-500">*</span></label>
                    <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" required>
                </div>

                {{-- Atas Nama --}}
                <div>
                    <label for="account_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Atas Nama <span class="text-red-500">*</span></label>
                    <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="account_name" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
                </div>

                {{-- No Rekening --}}
                <div>
                    <label for="account_number" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Rekening</label>
                    <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number) }}">
                </div>

                {{-- Hubungkan ke COA --}}
                <div>
                    <label for="chart_of_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Hubungkan ke Akun (COA) <span class="text-red-500">*</span></label>
                    <select class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="chart_of_account_id" name="chart_of_account_id" required>
                        <option value="" disabled>-- Pilih Akun Aset --</option>
                        @foreach ($assetAccounts as $asset)
                            <option value="{{ $asset->account_id }}" @selected(old('chart_of_account_id', $account->chart_of_account_id) == $asset->account_id)>
                                {{ $asset->account_number }} - {{ $asset->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Switch Active --}}
                <div class="md:col-span-2 pt-2">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_active" class="font-medium text-gray-700 cursor-pointer">Status Aktif</label>
                            <p class="text-gray-500 text-xs">Akun aktif dapat dipilih dalam transaksi pembayaran.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('company-bank-accounts.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Update Akun
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    });
</script>
@endpush