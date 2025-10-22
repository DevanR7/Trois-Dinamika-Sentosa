@extends('layouts.app') {{-- Pastikan ini nama layout utama Anda --}}

@section('content')
<div class="container py-4">
    
    {{-- JUDUL HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Pengaturan Akun</h2>
    </div>

    <div class="row">
        
        {{-- KOLOM KIRI (PROFIL RINGKAS) --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 2rem;">
                <div class="card-body text-center">
                    
                    {{-- Avatar Inisial --}}
                    <div class="d-inline-flex align-items-center justify-content-center 
                                bg-primary bg-opacity-10 text-primary rounded-circle mb-3" 
                         style="width: 100px; height: 100px; font-size: 2.5rem;">
                         {{-- Ambil huruf pertama dari nama lengkap --}}
                        {{ substr($user->full_name, 0, 1) }}
                    </div>

                    <h4 class="fw-bold mb-0">{{ $user->full_name }}</h4>
                    <p class="text-muted mb-1">{{ $user->username }}</p>
                    <p class="text-muted small mb-2">{{ $user->email }}</p>
                    
                    {{-- Role Badge --}}
                    <span class="badge rounded-pill bg-light text-dark border shadow-sm">
                        <i class="bi bi-shield-check me-1"></i>
                        {{ $user->getRoleNames()->first() ?? 'User' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (FORM PENGATURAN) --}}
        <div class="col-lg-8">
            
            {{-- BLOK FORM DENGAN TABS --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab" aria-controls="info-tab-pane" aria-selected="true">
                                <i class="bi bi-person-fill me-2"></i>Informasi Profil
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-tab-pane" type="button" role="tab" aria-controls="password-tab-pane" aria-selected="false">
                                <i class="bi bi-key-fill me-2"></i>Ubah Password
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <div class="tab-content" id="profileTabsContent">
                        
                        {{-- TAB 1: INFORMASI PROFIL --}}
                        <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab" tabindex="0">
                            <form method="post" action="{{ route('profile.update') }}">
                                @csrf
                                @method('patch')

                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                                           id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                                    @error('full_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                           id="username" name="username" value="{{ old('username', $user->username) }}" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nik" class="form-label">NIK (Opsional)</label>
                                    <input type="text" class="form-control @error('nik') is-invalid @enderror" 
                                           id="nik" name="nik" value="{{ old('nik', $user->nik) }}">
                                    @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">No. Telepon (Opsional)</label>
                                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                           id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}">
                                    @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat (Opsional)</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>
                        
                        {{-- TAB 2: UBAH PASSWORD --}}
                        <div class="tab-pane fade" id="password-tab-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                            {{-- Tampilkan error validasi password --}}
                            @if ($errors->updatePassword->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->updatePassword->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            
                            <form method="post" action="{{ route('profile.password.update') }}">
                                @csrf
                                @method('put')

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                           id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                                           id="password" name="password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Simpan Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div> {{-- Akhir Card Tabs --}}

            {{-- DANGER ZONE (HAPUS AKUN) --}}
            <div class="card shadow-sm border-danger mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h5 class="card-title text-danger mb-0">Hapus Akun</h5>
                            <p class="text-muted small mb-0">
                                Tindakan ini akan mengarsipkan akun Anda dan tidak bisa dibatalkan.
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline-danger mt-3 mt-md-0" id="btn-delete-account">
                            <i class="bi bi-trash me-2"></i>Hapus Akun Saya
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Form tersembunyi untuk proses delete --}}
<form id="delete-account-form" action="{{ route('profile.destroy') }}" method="POST" class="d-none">
    @csrf
    @method('delete')
    <input type="password" name="password" id="delete-password-input">
</form>
@endsection

@push('scripts')
{{-- Script SweetAlert dari langkah sebelumnya akan tetap berfungsi --}}
{{-- Anda tidak perlu menempelkan ulang script JS jika sudah ada di file --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Notifikasi Toast untuk Sukses ---
        const sessionStatus = @json(session('status'));
        if (sessionStatus) {
            let title = '';
            if (sessionStatus === 'profile-updated') {
                title = 'Profil berhasil diperbarui.';
            } else if (sessionStatus === 'password-updated') {
                title = 'Password berhasil diperbarui.';
            }

            if (title) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: title,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        }

        // --- Konfirmasi Hapus Akun ---
        const deleteButton = document.getElementById('btn-delete-account');
        const deleteForm = document.getElementById('delete-account-form');
        const passwordInput = document.getElementById('delete-password-input');

        if (deleteButton) {
            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Anda Yakin?',
                    // Mengubah teks karena kita pakai Soft Delete
                    text: "Akun Anda akan diarsipkan. Masukkan password Anda untuk konfirmasi.",
                    icon: 'warning',
                    input: 'password', 
                    inputPlaceholder: 'Masukkan password Anda',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan Akun!', // Mengubah teks tombol
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password wajib diisi');
                        }
                        return password; 
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        passwordInput.value = result.value;
                        deleteForm.submit();
                    }
                });
            });
        }

        // Tampilkan error jika password untuk hapus salah
        const deleteErrors = @json($errors->userDeletion->get('password'));
        if (deleteErrors.length > 0) {
             Swal.fire({
                icon: 'error',
                title: 'Gagal Mengarsipkan Akun',
                text: deleteErrors[0] // Tampilkan pesan error pertama
            });
        }

    });
</script>
@endpush