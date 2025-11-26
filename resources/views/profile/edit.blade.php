@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan Akun</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola informasi pribadi dan keamanan akun Anda.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI: PROFIL RINGKAS (Span 4) --}}
        <div class="lg:col-span-4">
            <div class="dashboard-card p-0 overflow-hidden sticky top-20 shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="p-8 flex flex-col items-center text-center bg-slate-50/30 border-b border-slate-100">
                    
                    {{-- Avatar --}}
                    <div class="w-28 h-28 bg-indigo-600 text-white rounded-full flex items-center justify-center mb-4 border-4 border-white shadow-lg text-4xl font-bold select-none">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>

                    <h4 class="text-xl font-bold text-slate-800">{{ $user->full_name }}</h4>
                    <p class="text-sm text-indigo-600 font-medium mb-1 font-mono">{{ '@' . $user->username }}</p>
                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    
                    {{-- Role Badge --}}
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 capitalize shadow-sm">
                            <i class="material-icons text-[14px] mr-1">verified_user</i>
                            {{ $user->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </div>
                </div>
                
                {{-- Info Tambahan --}}
                <div class="p-5 bg-white text-center">
                    <p class="text-xs text-slate-400 flex items-center justify-center gap-1">
                        <i class="material-icons text-[14px]">calendar_today</i>
                        Bergabung sejak {{ $user->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: FORM PENGATURAN (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- TAB CONTAINER --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 min-h-[500px]">
                
                {{-- Tabs Header --}}
                <div class="border-b border-slate-200 bg-white px-6 flex items-center gap-6 overflow-x-auto no-scrollbar">
                    <button id="tab-info-link" class="tab-link active group inline-flex items-center py-4 px-1 border-b-2 border-indigo-600 font-bold text-sm text-indigo-600 transition-colors whitespace-nowrap focus:outline-none" data-target="#info-tab">
                        <i class="material-icons text-[20px] mr-2 group-[.active]:text-indigo-600 text-slate-400">person</i>
                        Informasi Profil
                    </button>
                    <button id="tab-password-link" class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors whitespace-nowrap focus:outline-none" data-target="#password-tab">
                        <i class="material-icons text-[20px] mr-2 group-[.active]:text-indigo-600 text-slate-400">lock</i>
                        Ubah Password
                    </button>
                </div>
                
                <div class="p-6 md:p-8 bg-white">
                    
                    {{-- TAB 1: INFORMASI PROFIL --}}
                    <div id="info-tab" class="tab-content animate-enter">
                        <h5 class="text-base font-bold text-slate-800 mb-6 border-b border-slate-100 pb-2">Perbarui Data Pribadi</h5>
                        
                        <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="full_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Lengkap</label>
                                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-input" required>
                                    @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="username" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Username</label>
                                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="form-input font-medium text-slate-700" required>
                                    @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Email</label>
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-input" required>
                                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Data Tambahan --}}
                                <div class="md:col-span-2 pt-2">
                                    <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wide mb-4 border-b border-indigo-50 pb-2">Data Kontak & Alamat</h6>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="nik" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">NIK</label>
                                            <input type="text" name="nik" id="nik" value="{{ old('nik', $user->nik) }}" class="form-input font-mono" placeholder="Nomor Induk Kependudukan">
                                            @error('nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label for="phone_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">No. Telepon</label>
                                            <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-input" placeholder="0812...">
                                            @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="md:col-span-2">
                                            <label for="address" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Alamat Lengkap</label>
                                            <textarea name="address" id="address" rows="3" class="form-textarea">{{ old('address', $user->address) }}</textarea>
                                            @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-6 border-t border-slate-100">
                                <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                                    <i class="material-icons text-[20px]">save</i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 2: UBAH PASSWORD --}}
                    <div id="password-tab" class="tab-content hidden animate-enter">
                        <h5 class="text-base font-bold text-slate-800 mb-6 border-b border-slate-100 pb-2">Perbarui Kata Sandi</h5>
                        
                        <form method="post" action="{{ route('profile.password.update') }}" class="space-y-6 max-w-lg">
                            @csrf
                            @method('put')

                            <div>
                                <label for="current_password" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Password Saat Ini</label>
                                <div class="relative">
                                    <input type="password" name="current_password" id="current_password" class="form-input pr-10" required>
                                    <button type="button" id="toggle-current-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                                        <i class="material-icons text-lg">visibility_off</i>
                                    </button>
                                </div>
                                @error('current_password', 'updatePassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Password Baru</label>
                                <div class="relative">
                                    <input type="password" name="password" id="password" class="form-input pr-10" required>
                                    <button type="button" id="toggle-new-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                                        <i class="material-icons text-lg">visibility_off</i>
                                    </button>
                                </div>
                                @error('password', 'updatePassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Konfirmasi Password Baru</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" required>
                                @error('password_confirmation', 'updatePassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex justify-start pt-4">
                                <button type="submit" class="h-[48px] px-8 bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2">
                                    <i class="material-icons text-[20px]">lock_reset</i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> {{-- Akhir Card Tabs --}}

            {{-- DANGER ZONE (HAPUS AKUN) --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm border border-red-100">
                <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4 bg-red-50/30">
                    <div>
                        <h5 class="text-sm font-bold text-red-700 mb-1 flex items-center gap-2">
                            <i class="material-icons text-lg">warning</i> Hapus Akun
                        </h5>
                        <p class="text-xs text-red-600/80">Tindakan ini akan mengarsipkan akun Anda dan tidak bisa dibatalkan secara mandiri.</p>
                    </div>
                    <button type="button" id="btn-delete-account" 
                            class="h-[42px] px-5 border border-red-200 shadow-sm text-xs font-bold rounded-lg text-red-700 bg-white hover:bg-red-50 hover:border-red-300 transition whitespace-nowrap flex items-center gap-2">
                        <i class="material-icons text-[18px]">delete_forever</i> Hapus Akun Saya
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
        
        // --- 1. PASSWORD TOGGLE ---
        function setupToggle(inputId, btnId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(btnId);
            if(!input || !btn) return;
            
            btn.addEventListener('click', () => {
                const icon = btn.querySelector('i');
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                icon.innerText = type === 'password' ? 'visibility_off' : 'visibility';
            });
        }
        setupToggle('current_password', 'toggle-current-password');
        setupToggle('password', 'toggle-new-password');

        // --- 2. TAB SWITCHING ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        const hash = window.location.hash;

        const activateTab = (targetId, clickedLink) => {
            tabLinks.forEach(l => {
                l.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
                l.classList.add('text-slate-500', 'border-transparent');
                l.querySelector('i').classList.remove('text-indigo-600');
                l.querySelector('i').classList.add('text-slate-400');
            });

            clickedLink.classList.add('active', 'text-indigo-600', 'border-indigo-600');
            clickedLink.classList.remove('text-slate-500', 'border-transparent');
            clickedLink.querySelector('i').classList.add('text-indigo-600');
            clickedLink.querySelector('i').classList.remove('text-slate-400');

            tabContents.forEach(content => content.classList.add('hidden'));
            document.querySelector(targetId).classList.remove('hidden');
        };

        // Initialize Tab
        let defaultTabLink = document.getElementById('tab-info-link');
        if (hash === '#password-tab') {
            defaultTabLink = document.getElementById('tab-password-link');
        }
        // Force tab password jika ada error validasi password
        @if($errors->updatePassword->any() || $errors->userDeletion->any())
            defaultTabLink = document.getElementById('tab-password-link');
        @endif

        if (defaultTabLink) activateTab(defaultTabLink.dataset.target, defaultTabLink);
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                activateTab(this.dataset.target, this);
            });
        });

        // --- 3. NOTIFIKASI ---
        const sessionStatus = @json(session('status'));
        if (sessionStatus) {
            let title = '';
            if (sessionStatus === 'profile-updated') title = 'Profil berhasil diperbarui.';
            else if (sessionStatus === 'password-updated') title = 'Password berhasil diperbarui.';
            if (title) window.showToast(title, 'success');
        }
        
        const deleteErrors = @json($errors->userDeletion->get('password'));
        if (deleteErrors.length > 0) {
             window.showToast(deleteErrors[0], 'error');
        }

        // --- 4. KONFIRMASI HAPUS AKUN ---
        const deleteButton = document.getElementById('btn-delete-account');
        const deleteForm = document.getElementById('delete-account-form');
        const passwordInput = document.getElementById('delete-password-input');

        if (deleteButton) {
            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Arsipkan Akun?',
                    html: `<div class="mb-4 text-sm text-slate-600">Masukkan password Anda untuk mengonfirmasi penghapusan akun.</div>`,
                    input: 'password', 
                    inputPlaceholder: 'Password Anda',
                    inputAttributes: { 
                        autocapitalize: 'off', 
                        autocorrect: 'off',
                        class: 'swal2-input text-center' 
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'bg-white rounded-xl border border-slate-100 shadow-2xl p-6',
                        confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-md',
                        cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                    },
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password wajib diisi');
                        }
                        return password; 
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        passwordInput.value = result.value;
                        deleteForm.submit();
                    }
                });
            });
        }
    });
</script>
@endpush