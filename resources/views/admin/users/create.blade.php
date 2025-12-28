@extends('admin.layouts.app')

@section('title', 'Tambah User Baru')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah User</h1>
                <p class="page-subtitle">Buat akun baru untuk staf atau admin.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- LEFT COLUMN: Akun & Login --}}
                <div class="card h-fit">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Akun</h3>
                    </div>
                    <div class="card-body space-y-5">
                        
                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="form-label label-required">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-input @error('full_name') is-invalid @enderror" 
                                   value="{{ old('full_name') }}" placeholder="Contoh: Budi Santoso" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Username & Email --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="form-label label-required">Username</label>
                                <input type="text" name="username" class="form-input @error('username') is-invalid @enderror" 
                                       value="{{ old('username') }}" required>
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Email</label>
                                <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Role --}}
                        <div>
                            <label class="form-label label-required">Role (Hak Akses)</label>
                            <select name="role" class="tom-select" required placeholder="Pilih Role...">
                                <option value="">Pilih Role...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Password --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                            <div>
                                <label class="form-label label-required">Password</label>
                                <input type="password" name="password" class="form-input @error('password') is-invalid @enderror" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-input" required>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- RIGHT COLUMN: Detail Personal --}}
                <div class="card h-fit">
                    <div class="card-header">
                        <h3 class="card-header-title">Detail Personal</h3>
                    </div>
                    <div class="card-body space-y-5">
                        
                        {{-- Phone & NIK --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="form-label label-optional">No. Telepon / WA</label>
                                <input type="text" name="phone_number" class="form-input @error('phone_number') is-invalid @enderror" 
                                       value="{{ old('phone_number') }}">
                                @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-optional">NIK Karyawan</label>
                                <input type="text" name="nik" class="form-input @error('nik') is-invalid @enderror" 
                                       value="{{ old('nik') }}">
                                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Sales Code --}}
                        <div>
                            <label class="form-label label-optional">Kode Sales</label>
                            <input type="text" name="sales_code" class="form-input uppercase @error('sales_code') is-invalid @enderror" 
                                   value="{{ old('sales_code') }}" placeholder="Cth: SL01">
                            <div class="form-hint">Diisi khusus untuk tim sales agar tercetak di invoice.</div>
                            @error('sales_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label class="form-label label-optional">Alamat Lengkap</label>
                            <textarea name="address" class="form-textarea" rows="4">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-sm mr-2">save</i> Simpan User
                </button>
            </div>

        </form>
    </div>

@endsection