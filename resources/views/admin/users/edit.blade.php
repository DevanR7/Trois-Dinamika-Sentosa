@extends('admin.layouts.app')

@section('title', 'Edit User')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit User: {{ $user->full_name }}</h1>
                <p class="page-subtitle">Perbarui informasi dan hak akses pengguna.</p>
            </div>
            <div class="flex gap-3">
                @if(Auth::id() !== $user->user_id)
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                        <i class="material-icons text-sm mr-1">delete</i> Hapus
                    </button>
                @endif
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.users.update', $user->user_id) }}" method="POST">
            @csrf
            @method('PUT')

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
                                   value="{{ old('full_name', $user->full_name) }}" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Username & Email --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="form-label label-required">Username</label>
                                <input type="text" name="username" class="form-input @error('username') is-invalid @enderror" 
                                       value="{{ old('username', $user->username) }}" required>
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Email</label>
                                <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Role --}}
                        <div>
                            <label class="form-label label-required">Role (Hak Akses)</label>
                            <select name="role" class="tom-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" 
                                        {{ (old('role', $user->getRoleNames()->first()) == $role->name) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Password Change --}}
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 mt-4">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Ubah Password (Opsional)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label text-[10px]">Password Baru</label>
                                    <input type="password" name="password" class="form-input @error('password') is-invalid @enderror">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Konfirmasi Password</label>
                                    <input type="password" name="password_confirmation" class="form-input">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-2 italic">* Kosongkan jika tidak ingin mengubah password.</p>
                        </div>

                    </div>
                </div>

                {{-- RIGHT COLUMN: Detail Personal --}}
                <div class="card h-fit">
                    <div class="card-header">
                        <h3 class="card-header-title">Detail Personal</h3>
                    </div>
                    <div class="card-body space-y-5">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="form-label label-optional">No. Telepon / WA</label>
                                <input type="text" name="phone_number" class="form-input @error('phone_number') is-invalid @enderror" 
                                       value="{{ old('phone_number', $user->phone_number) }}">
                                @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-optional">NIK Karyawan</label>
                                <input type="text" name="nik" class="form-input @error('nik') is-invalid @enderror" 
                                       value="{{ old('nik', $user->nik) }}">
                                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label label-optional">Kode Sales</label>
                            <input type="text" name="sales_code" class="form-input uppercase @error('sales_code') is-invalid @enderror" 
                                   value="{{ old('sales_code', $user->sales_code) }}">
                            <div class="form-hint">Kode unik untuk Sales (opsional).</div>
                            @error('sales_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="form-label label-optional">Alamat Lengkap</label>
                            <textarea name="address" class="form-textarea" rows="4">{{ old('address', $user->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        {{-- Hidden Delete Form --}}
        @if(Auth::id() !== $user->user_id)
            <form id="deleteForm" action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus User?',
            text: "Akun ini akan dipindahkan ke sampah (soft delete).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush