@extends('layouts.app')

@section('title', 'Buat Akun COA')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('chart-of-accounts.index') }}" class="hover:text-indigo-600 transition-colors">Chart of Accounts</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Akun Baru (COA)</h1>
        </div>
        <a href="{{ route('chart-of-accounts.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('chart-of-accounts.store') }}" method="POST" id="coa-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">account_tree</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Form Akun</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Nomor Akun --}}
                <div>
                    <label for="account_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" class="form-input font-mono text-indigo-600 font-bold" placeholder="Contoh: 1-1001" required>
                    @error('account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Nama Akun --}}
                <div>
                    <label for="account_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="account_name" id="account_name" value="{{ old('account_name') }}" class="form-input" placeholder="Contoh: Kas Besar" required>
                    @error('account_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tipe Akun --}}
                <div>
                    <label for="account_type" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipe Akun <span class="text-red-500">*</span></label>
                    <select name="account_type" id="account_type" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Tipe --</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type }}" @selected(old('account_type') == $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('account_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Saldo Normal --}}
                <div>
                    <label for="normal_balance" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Saldo Normal <span class="text-red-500">*</span></label>
                    <select name="normal_balance" id="normal_balance" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Saldo --</option>
                        @foreach ($normalBalances as $balance)
                            <option value="{{ $balance }}" @selected(old('normal_balance') == $balance)>{{ $balance }}</option>
                        @endforeach
                    </select>
                    @error('normal_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Induk --}}
                <div class="md:col-span-2">
                    <label for="parent_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Induk (Opsional)</label>
                    <select name="parent_account_id" id="parent_account_id" class="form-input select2-basic">
                        <option value="">-- Tidak Ada Induk (Jadikan Akun Parent) --</option>
                        @foreach ($parentAccounts as $parent)
                            <option value="{{ $parent->account_id }}" @selected(old('parent_account_id') == $parent->account_id)>
                                {{ $parent->account_number }} - {{ $parent->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-slate-400 flex items-center gap-1"><i class="material-icons text-[12px]">info</i> Pilih ini jika Anda ingin mengelompokkan akun (Sub-akun).</p>
                    @error('parent_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi (Opsional)</label>
                    <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Status Switch --}}
                <div class="md:col-span-2 pt-4 border-t border-slate-100">
                    <label class="flex items-center cursor-pointer group w-fit">
                        <div class="relative">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition-colors">Aktifkan Akun Ini</span>
                    </label>
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('chart-of-accounts.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan Akun
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