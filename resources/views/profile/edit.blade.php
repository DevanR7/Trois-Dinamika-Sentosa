@extends('layouts.app') {{-- Pastikan ini nama layout utama Anda --}}

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Profil Saya</h2>

    <div class="row">
        <div class="col-lg-4">
            {{-- KARTU PROFIL RINGKASAN (Permintaan Anda) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center">
                    {{-- Ganti dengan foto profil jika ada, jika tidak, gunakan ikon --}}
                    <i class="bi bi-person-circle display-1 text-primary"></i>
                    
                    <h4 class="card-title mt-3">{{ $user->full_name }}</h4>
                    <p class="text-muted mb-0">{{ $user->username }}</p>
                    <p class="text-muted">{{ $user->email }}</p>
                    
                    <span class="badge bg-success fs-6">
                        <i class="bi bi-shield-check me-1"></i>
                        {{-- Ambil role pertama user --}}
                        {{ $user->getRoleNames()->first() ?? 'User' }}
                    </span>
                </div>
            </div>

            {{-- KARTU HAPUS AKUN --}}
            <div class="card shadow-sm border-0 mb-4">
                <h5 class="card-header text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Hapus Akun
                </h5>
                <div class="card-body">
                    <p class="text-muted small">
                        Setelah akun Anda dihapus, semua data akan hilang secara permanen. 
                        Tindakan ini tidak bisa dibatalkan.
                    </p>
                    {{-- Tombol ini akan mentrigger SweetAlert --}}
                    <button type="button" class="btn btn-danger w-100" id="btn-delete-account">
                        Hapus Akun Saya
                    </button>
                    
                    {{-- Form tersembunyi untuk proses delete --}}
                    <form id="delete-account-form" action="{{ route('profile.destroy') }}" method="POST" class="d-none">
                        @csrf
                        @method('delete')
                        <input type="password" name="password" id="delete-password-input">
                    </form>
                </div>
            </div>

        </div>

        <div class="col-lg-8">
            {{-- KARTU UPDATE PROFIL --}}
            <div class="card shadow-sm border-0 mb-4">
                <h5 class="card-header">Informasi Profil</h5>
                <div class="card-body">
                    <p class="text-muted small mb-3">Perbarui informasi profil dan alamat email akun Anda.</p>
                    
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

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>

            {{-- KARTU UPDATE PASSWORD --}}
            <div class="card shadow-sm border-0 mb-4">
                <h5 class="card-header">Ubah Password</h5>
                <div class="card-body">
                    <p class="text-muted small mb-3">Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.</p>
                    
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
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password" name="password" required>
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
    </div>
</div>
@endsection

@push('scripts')
{{-- Pastikan Anda sudah memuat SweetAlert2 di layout utama Anda --}}
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
                    text: "Semua data Anda akan dihapus permanen. Masukkan password Anda untuk konfirmasi.",
                    icon: 'warning',
                    input: 'password', // Minta input password
                    inputPlaceholder: 'Masukkan password Anda',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Akun Saya!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password wajib diisi');
                        }
                        return password; // Kembalikan nilai password
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika dikonfirmasi, masukkan password ke form tersembunyi dan submit
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
                title: 'Gagal Menghapus Akun',
                text: deleteErrors[0] // Tampilkan pesan error pertama
            });
        }

    });
</script>
@endpush