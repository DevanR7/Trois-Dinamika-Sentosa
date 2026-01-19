@extends('admin.layouts.app')

@section('title', 'Edit User')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Edit User</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Perbarui data akun <span class="font-bold text-indigo-600">{{ $user->full_name }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Kembali</span>
        </a>
    </div>

    <form action="{{ route('admin.users.update', $user->user_id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI --}}
            <div class="xl:col-span-8 space-y-6">
                
                {{-- Card: Informasi Akun --}}
                <div class="card p-6 h-full">
                    <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 pb-3 mb-5">
                        <h3 class="card-header-title flex items-center gap-2">
                            <i class="material-icons text-indigo-500">manage_accounts</i> Informasi Akun
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="form-label label-required">Nama Lengkap</label>
                            <input type="text" name="full_name" 
                                   class="form-input @error('full_name') is-invalid @enderror" 
                                   value="{{ old('full_name', $user->full_name) }}" required>
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
                                           value="{{ old('username', $user->username) }}" required>
                                </div>
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="form-label label-required">Alamat Email</label>
                                <input type="email" name="email" 
                                       class="form-input @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Ubah Password (Optional) --}}
                        <div x-data="{ show: false }" class="mt-4 bg-amber-50 dark:bg-amber-900/10 p-5 rounded-xl border border-amber-100 dark:border-amber-800/30">
                            <h4 class="text-xs font-bold text-amber-600 dark:text-amber-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <i class="material-icons text-sm">vpn_key</i> Ubah Password (Opsional)
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Password Baru</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" name="password" 
                                               class="form-input pr-10 @error('password') is-invalid @enderror" 
                                               placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                                            <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                                        </button>
                                    </div>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input :type="show ? 'text' : 'password'" name="password_confirmation" 
                                           class="form-input" 
                                           placeholder="Ulangi password baru">
                                </div>
                            </div>
                            <p class="text-[10px] text-amber-600/70 mt-2 italic">
                                *Hanya isi kolom ini jika Anda ingin mereset password pengguna.
                            </p>
                        </div>

                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN --}}
            <div class="xl:col-span-4 space-y-6">
                
                {{-- Card Role --}}
                <div class="card p-5 bg-white dark:bg-slate-800">
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                        <h3 class="card-header-title text-sm">Role & Akses</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label label-required">Role System</label>
                            
                            {{-- Logic Proteksi: Superadmin tidak bisa ganti role dirinya sendiri agar tidak terkunci --}}
                            @if($user->hasRole('superadmin') && Auth::user()->user_id == $user->user_id)
                                <input type="hidden" name="role" value="superadmin">
                                <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="Superadmin" readonly>
                                <p class="text-[10px] text-slate-400 mt-1">Anda tidak bisa mengubah role Superadmin Anda sendiri.</p>
                            @else
                                <select name="role" class="tom-select @error('role') is-invalid @enderror" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" 
                                            {{ old('role', $user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="form-label">Kode Sales</label>
                            <input type="text" name="sales_code" 
                                   class="form-input font-mono uppercase" 
                                   value="{{ old('sales_code', $user->sales_code) }}">
                        </div>

                        <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
                             <span class="text-xs font-bold text-slate-500">Status Akun:</span>
                             @if($user->is_approved)
                                <span class="badge bg-emerald-100 text-emerald-700 border-emerald-200">Aktif / Approved</span>
                             @else
                                <span class="badge bg-amber-100 text-amber-700 border-amber-200">Pending Approval</span>
                             @endif
                        </div>
                    </div>
                </div>

                {{-- Card Profil --}}
                <div class="card p-5">
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                        <h3 class="card-header-title text-sm">Data Pribadi</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-input" value="{{ old('nik', $user->nik) }}">
                        </div>

                        <div>
                            <label class="form-label">No. Handphone</label>
                            <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number', $user->phone_number) }}">
                        </div>

                        <div>
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-textarea h-24">{{ old('address', $user->address) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action --}}
                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3">
                        <i class="material-icons text-[18px]">save</i>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-full justify-center">
                        Batal
                    </a>
                </div>

            </div>
        </div>
    </form>

@endsection