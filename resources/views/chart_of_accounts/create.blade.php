@extends('layouts.app')

@section('title', 'Buat Akun COA')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('chart-of-accounts.index') }}" class="hover:text-indigo-600 transition">Chart of Accounts</a>
                <span>/</span>
                <span class="text-gray-800">Baru</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Akun Baru (COA)</h2>
            <p class="text-sm text-gray-500 mt-1">Tambahkan akun buku besar untuk pencatatan akuntansi.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('chart-of-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('chart-of-accounts.store') }}" method="POST" id="coa-form">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">account_tree</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Akun</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Nomor Akun --}}
                <div>
                    <label for="account_number" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="Contoh: 1-1001" required>
                    @error('account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Nama Akun --}}
                <div>
                    <label for="account_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_name" id="account_name" value="{{ old('account_name') }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Kas Besar" required>
                    @error('account_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tipe Akun --}}
                <div>
                    <label for="account_type" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Akun <span class="text-red-500">*</span></label>
                    <select name="account_type" id="account_type" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled selected>-- Pilih Tipe --</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type }}" @selected(old('account_type') == $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('account_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Saldo Normal --}}
                <div>
                    <label for="normal_balance" class="block text-xs font-bold text-gray-500 uppercase mb-1">Saldo Normal <span class="text-red-500">*</span></label>
                    <select name="normal_balance" id="normal_balance" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="" disabled selected>-- Pilih Saldo --</option>
                        @foreach ($normalBalances as $balance)
                            <option value="{{ $balance }}" @selected(old('normal_balance') == $balance)>{{ $balance }}</option>
                        @endforeach
                    </select>
                    @error('normal_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Induk --}}
                <div class="md:col-span-2">
                    <label for="parent_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Induk (Opsional)</label>
                    <select name="parent_account_id" id="parent_account_id" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Tidak Ada Induk (Jadikan Akun Parent) --</option>
                        @foreach ($parentAccounts as $parent)
                            <option value="{{ $parent->account_id }}" @selected(old('parent_account_id') == $parent->account_id)>
                                {{ $parent->account_number }} - {{ $parent->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Pilih ini jika Anda ingin mengelompokkan akun (Sub-akun).</p>
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi (Opsional)</label>
                    <textarea name="description" id="description" rows="2" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description') }}</textarea>
                </div>

                {{-- Status Switch --}}
                <div class="md:col-span-2 pt-2 border-t border-gray-100">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="is_active" name="is_active" type="checkbox" value="1" checked class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_active" class="font-medium text-gray-700 cursor-pointer">Aktifkan Akun Ini</label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('chart-of-accounts.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">save</i> Simpan Akun
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('coa-form').addEventListener('submit', function(e) {
        const accNum = document.getElementById('account_number').value;
        const accName = document.getElementById('account_name').value;
        if(!accNum || !accName) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Nomor dan Nama Akun wajib diisi.',
                confirmButtonColor: '#6366f1'
            });
        }
    });
</script>
@endpush