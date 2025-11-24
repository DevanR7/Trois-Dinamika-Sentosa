@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Pengaturan Akun</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola informasi pribadi dan keamanan akun Anda.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI (PROFIL RINGKAS) --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden">
                <div class="p-8 flex flex-col items-center text-center border-b border-gray-100">
                    
                    {{-- Avatar --}}
                    <div class="w-24 h-24 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mb-4 shadow-inner border-4 border-white ring-2 ring-indigo-50">
                        <span class="text-4xl font-bold">{{ substr($user->full_name, 0, 1) }}</span>
                    </div>

                    <h4 class="text-xl font-bold text-gray-900">{{ $user->full_name }}</h4>
                    <p class="text-sm text-gray-500 font-medium mb-1">{{ '@' . $user->username }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    
                    {{-- Role Badge --}}
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 capitalize">
                            <i class="material-icons text-[14px] mr-1 text-gray-400">verified_user</i>
                            {{ $user->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </div>
                </div>
                
                {{-- Menu Navigasi Samping (Optional / Info Tambahan) --}}
                <div class="p-4 bg-gray-50">
                    <div class="text-xs text-center text-gray-400">
                        Member sejak {{ $user->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (FORM PENGATURAN) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- TAB CONTAINER --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {{-- Tabs Header --}}
                <div class="border-b border-gray-200 bg-white px-6 flex items-center gap-6 overflow-x-auto">
                    <button class="tab-link active group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors text-indigo-600 border-indigo-600 focus:outline-none" data-target="#info-tab">
                        <i class="material-icons text-lg mr-2 group-[.active]:text-indigo-600 text-gray-400">person</i>
                        Informasi Profil
                    </button>
                    <button class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors focus:outline-none" data-target="#password-tab">
                        <i class="material-icons text-lg mr-2 group-[.active]:text-indigo-600 text-gray-400">vpn_key</i>
                        Ubah Password
                    </button>
                </div>
                
                <div class="p-6">
                    
                    {{-- TAB 1: INFORMASI PROFIL --}}
                    <div id="info-tab" class="tab-content">
                        <form method="post" action="{{ route('profile.update') }}">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="full_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Lengkap</label>
                                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="username" class="block text-xs font-bold text-gray-500 uppercase mb-1">Username</label>
                                        <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-gray-200 my-2"></div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nik" class="block text-xs font-bold text-gray-500 uppercase mb-1">NIK (Opsional)</label>
                                        <input type="text" name="nik" id="nik" value="{{ old('nik', $user->nik) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="phone_number" class="block text-xs font-bold text-gray-500 uppercase mb-1">No. Telepon (Opsional)</label>
                                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="address" class="block text-xs font-bold text-gray-500 uppercase mb-1">Alamat (Opsional)</label>
                                    <textarea name="address" id="address" rows="3" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('address', $user->address) }}</textarea>
                                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex justify-end pt-4">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 2: UBAH PASSWORD --}}
                    <div id="password-tab" class="tab-content hidden">
                        
                        @if ($errors->updatePassword->any())
                            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                                <ul class="list-disc list-inside text-xs text-red-700">
                                    @foreach ($errors->updatePassword->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="post" action="{{ route('profile.password.update') }}">
                            @csrf
                            @method('put')

                            <div class="grid grid-cols-1 gap-6 max-w-lg">
                                <div>
                                    <label for="current_password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Password Saat Ini</label>
                                    <input type="password" name="current_password" id="current_password" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                </div>

                                <div>
                                    <label for="password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Password Baru</label>
                                    <input type="password" name="password" id="password" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-xs font-bold text-gray-500 uppercase mb-1">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                </div>

                                <div class="flex justify-start pt-4">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                                        Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div> {{-- Akhir Card Tabs --}}

            {{-- DANGER ZONE (HAPUS AKUN) --}}
            <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h5 class="text-base font-bold text-red-600 mb-1">Hapus Akun</h5>
                        <p class="text-sm text-gray-500">Tindakan ini akan mengarsipkan akun Anda dan tidak bisa dibatalkan.</p>
                    </div>
                    <button type="button" id="btn-delete-account" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition whitespace-nowrap">
                        <i class="material-icons text-lg mr-2">delete_forever</i> Hapus Akun Saya
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Form tersembunyi untuk proses delete --}}
<form id="delete-account-form" action="{{ route('profile.destroy') }}" method="POST" class="hidden">
    @csrf
    @method('delete')
    <input type="password" name="password" id="delete-password-input">
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- TAB SWITCHING LOGIC (TAILWIND) ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Reset active state
                tabLinks.forEach(l => {
                    l.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
                    l.classList.add('text-gray-500', 'border-transparent');
                    l.querySelector('i').classList.remove('text-indigo-600');
                    l.querySelector('i').classList.add('text-gray-400');
                });

                // Set active current link
                this.classList.add('active', 'text-indigo-600', 'border-indigo-600');
                this.classList.remove('text-gray-500', 'border-transparent');
                this.querySelector('i').classList.add('text-indigo-600');
                this.querySelector('i').classList.remove('text-gray-400');

                // Show target content
                const targetId = this.dataset.target;
                tabContents.forEach(content => content.classList.add('hidden'));
                document.querySelector(targetId).classList.remove('hidden');
            });
        });

        // --- NOTIFIKASI TOAST ---
        const sessionStatus = @json(session('status'));
        if (sessionStatus) {
            let title = '';
            if (sessionStatus === 'profile-updated') title = 'Profil berhasil diperbarui.';
            else if (sessionStatus === 'password-updated') title = 'Password berhasil diperbarui.';

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

        // --- KONFIRMASI HAPUS AKUN ---
        const deleteButton = document.getElementById('btn-delete-account');
        const deleteForm = document.getElementById('delete-account-form');
        const passwordInput = document.getElementById('delete-password-input');

        if (deleteButton) {
            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: "Akun Anda akan diarsipkan. Masukkan password Anda untuk konfirmasi.",
                    icon: 'warning',
                    input: 'password', 
                    inputPlaceholder: 'Masukkan password Anda',
                    inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Arsipkan Akun!',
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

        // Tampilkan error delete password jika ada
        const deleteErrors = @json($errors->userDeletion->get('password'));
        if (deleteErrors.length > 0) {
             Swal.fire({
                icon: 'error',
                title: 'Gagal Mengarsipkan Akun',
                text: deleteErrors[0]
            });
        }

    });
</script>
@endpush