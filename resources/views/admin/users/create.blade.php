@extends('admin.layouts.app')

@section('title', 'Tambah User Baru')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">User Baru</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Daftarkan pengguna baru untuk mengakses sistem.
            </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Batal</span>
        </a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI: KREDENSIAL AKUN (8 Kolom) --}}
            <div class="xl:col-span-8 space-y-6">
                
                {{-- Card: Informasi Login --}}
                <div class="card p-6 h-full">
                    <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 pb-3 mb-5">
                        <h3 class="card-header-title flex items-center gap-2">
                            <i class="material-icons text-indigo-500">lock</i> Kredensial Akun
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="form-label label-required">Nama Lengkap</label>
                            <input type="text" name="full_name" 
                                   class="form-input @error('full_name') is-invalid @enderror" 
                                   value="{{ old('full_name') }}" 
                                   placeholder="Contoh: Budi Santoso" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Username --}}
                            <div>
                                <label class="form-label label-required">Username</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">@</span>
                                    <input type="text" name="username" 
                                           class="form-input pl-8 @error('username') is-invalid @enderror" 
                                           value="{{ old('username') }}" 
                                           placeholder="budi_s" required>
                                </div>
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="form-label label-required">Alamat Email</label>
                                <input type="email" name="email" 
                                       class="form-input @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" 
                                       placeholder="budi@example.com" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Password (Alpine Toggle) --}}
                        <div x-data="{ show: false }" class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            
                            {{-- Password --}}
                            <div>
                                <label class="form-label label-required">Password</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="password" 
                                           class="form-input pr-10 @error('password') is-invalid @enderror" 
                                           placeholder="******" required>
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                                        <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                                    </button>
                                </div>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Konfirmasi Password --}}
                            <div>
                                <label class="form-label label-required">Konfirmasi Password</label>
                                <input :type="show ? 'text' : 'password'" name="password_confirmation" 
                                       class="form-input" 
                                       placeholder="Ulangi password" required>
                            </div>
                            
                            <div class="md:col-span-2">
                                <p class="text-[10px] text-slate-500 italic">
                                    <i class="material-icons text-[10px] align-middle">info</i>
                                    Minimal 8 karakter. Disarankan menggunakan kombinasi huruf dan angka.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: DATA PROFIL & ROLE (4 Kolom) --}}
            <div class="xl:col-span-4 space-y-6">
                
                {{-- Card: Role & Jabatan --}}
                <div class="card p-5 bg-white dark:bg-slate-800">
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                        <h3 class="card-header-title text-sm">Role & Jabatan</h3>
                    </div>

                    <div class="space-y-4">
                        {{-- Role Selection --}}
                        <div>
                            <label class="form-label label-required">Role System</label>
                            <select name="role" class="tom-select @error('role') is-invalid @enderror" required>
                                <option value="" selected disabled>Pilih Role...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Sales Code --}}
                        <div>
                            <label class="form-label">Kode Sales</label>
                            <input type="text" name="sales_code" 
                                   class="form-input font-mono uppercase @error('sales_code') is-invalid @enderror" 
                                   value="{{ old('sales_code') }}" 
                                   placeholder="Contoh: SL01">
                            <p class="text-[10px] text-slate-400 mt-1">*Wajib jika Role adalah Sales.</p>
                            @error('sales_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Card: Data Pribadi --}}
                <div class="card p-5">
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                        <h3 class="card-header-title text-sm">Data Pribadi</h3>
                    </div>

                    <div class="space-y-4">
                        {{-- NIK --}}
                        <div>
                            <label class="form-label">NIK (KTP)</label>
                            <input type="text" name="nik" class="form-input" value="{{ old('nik') }}" placeholder="Nomor Induk Kependudukan">
                        </div>

                        {{-- No HP --}}
                        <div>
                            <label class="form-label">No. Handphone</label>
                            <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number') }}" placeholder="08xxxxxxxx">
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label class="form-label">Alamat Domisili</label>
                            <textarea name="address" class="form-textarea h-24" placeholder="Alamat lengkap user...">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Sticky Action --}}
                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3">
                        <i class="material-icons text-[18px]">person_add</i>
                        Simpan User
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-full justify-center">
                        Batal
                    </a>
                </div>

            </div>
        </div>
    </form>

@endsection