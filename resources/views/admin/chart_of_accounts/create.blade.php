@extends('admin.layouts.app')

@section('title', 'Tambah Akun Baru')

@section('content')

    <div class="max-w-4xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah Akun (COA)</h1>
                <p class="page-subtitle">Daftarkan kode akun baru untuk keperluan jurnal.</p>
            </div>
            <a href="{{ route('admin.chart-of-accounts.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.chart-of-accounts.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Formulir Akun</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Kode & Nama --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="form-label label-required">Kode Akun</label>
                            <input type="text" name="account_number" 
                                   class="form-input @error('account_number') is-invalid @enderror" 
                                   placeholder="Cth: 1101" 
                                   value="{{ old('account_number') }}" required>
                            @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label label-required">Nama Akun</label>
                            <input type="text" name="account_name" 
                                   class="form-input @error('account_name') is-invalid @enderror" 
                                   placeholder="Cth: Kas Besar, Bank BCA, Pendapatan Jasa" 
                                   value="{{ old('account_name') }}" required>
                            @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Klasifikasi --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Kategori --}}
                        <div>
                            <label class="form-label label-required">Kategori Akun</label>
                            <select name="account_type" class="tom-select" required>
                                <option value="">Pilih Kategori...</option>
                                @foreach($accountTypes as $type)
                                    <option value="{{ $type }}" {{ old('account_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Menentukan posisi akun di Laporan Keuangan.</div>
                            @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Saldo Normal --}}
                        <div>
                            <label class="form-label label-required">Saldo Normal</label>
                            <select name="normal_balance" class="tom-select" required>
                                <option value="">Pilih Saldo...</option>
                                @foreach($normalBalances as $balance)
                                    <option value="{{ $balance }}" {{ old('normal_balance') == $balance ? 'selected' : '' }}>
                                        {{ $balance }} (Bertambah di posisi ini)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Debit (Aset/Beban) atau Kredit (Liabilitas/Ekuitas/Pendapatan).</div>
                            @error('normal_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    {{-- Hierarki & Status --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Parent Account --}}
                        <div>
                            <label class="form-label label-optional">Akun Induk (Parent)</label>
                            <select name="parent_account_id" class="tom-select">
                                <option value="">- Tidak Ada (Akun Utama) -</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->account_id }}" {{ old('parent_account_id') == $parent->account_id ? 'selected' : '' }}>
                                        {{ $parent->account_number }} - {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Pilih jika akun ini adalah sub-akun dari akun lain.</div>
                            @error('parent_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Status Aktif --}}
                        <div class="flex items-center h-full pt-6">
                            <div class="p-3 w-full bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Status Aktif</span>
                                    <p class="text-[10px] text-slate-500">Dapat dipilih saat penjurnalan.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="form-label label-optional">Keterangan Tambahan</label>
                        <textarea name="description" class="form-textarea" rows="2" placeholder="Catatan fungsi akun ini...">{{ old('description') }}</textarea>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Akun
                </button>
            </div>

        </form>
    </div>

@endsection