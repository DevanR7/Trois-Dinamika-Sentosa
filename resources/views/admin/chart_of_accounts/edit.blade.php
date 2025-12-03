@extends('admin.layouts.app')

@section('title', 'Edit Akun COA')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.chart-of-accounts.index') }}" class="hover:text-indigo-600 transition-colors">Chart of Accounts</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Akun</h1>
            <p class="text-slate-500 text-sm mt-1">Perbarui data akun: <span class="font-bold text-indigo-600 font-mono bg-indigo-50 px-1 rounded">{{ $chartOfAccount->account_number }}</span></p>
        </div>
        <a href="{{ route('admin.chart-of-accounts.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.chart-of-accounts.update', $chartOfAccount) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">edit_note</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Data Akun</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label for="account_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number" id="account_number" value="{{ old('account_number', $chartOfAccount->account_number) }}" class="form-input font-mono text-indigo-600 font-bold" required>
                    @error('account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_name" id="account_name" value="{{ old('account_name', $chartOfAccount->account_name) }}" class="form-input" required>
                    @error('account_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_type" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipe Akun <span class="text-red-500">*</span></label>
                    <select name="account_type" id="account_type" class="form-input select2-basic" required>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type }}" @selected(old('account_type', $chartOfAccount->account_type) == $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('account_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="normal_balance" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Saldo Normal <span class="text-red-500">*</span></label>
                    <select name="normal_balance" id="normal_balance" class="form-input select2-basic" required>
                        @foreach ($normalBalances as $balance)
                            <option value="{{ $balance }}" @selected(old('normal_balance', $chartOfAccount->normal_balance) == $balance)>{{ $balance }}</option>
                        @endforeach
                    </select>
                    @error('normal_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="parent_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Induk (Opsional)</label>
                    <select name="parent_account_id" id="parent_account_id" class="form-input select2-basic">
                        <option value="">-- Tidak Ada Induk (Jadikan Akun Parent) --</option>
                        @foreach ($parentAccounts as $parent)
                            @if($parent->account_id != $chartOfAccount->account_id)
                                <option value="{{ $parent->account_id }}" @selected(old('parent_account_id', $chartOfAccount->parent_account_id) == $parent->account_id)>
                                    {{ $parent->account_number }} - {{ $parent->account_name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi (Opsional)</label>
                    <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $chartOfAccount->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 pt-4 border-t border-slate-100">
                    <label class="flex items-center cursor-pointer group w-fit">
                        <div class="relative">
                            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $chartOfAccount->is_active)) class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition-colors">Aktifkan Akun Ini</span>
                    </label>
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('admin.chart-of-accounts.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Update Akun
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-basic').select2({ placeholder: '-- Pilih --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush