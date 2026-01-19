@extends('admin.layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Pengaturan Profil</h1>
            <p class="page-subtitle">Kelola informasi akun, foto profil, dan keamanan.</p>
        </div>
    </div>

    {{-- 1. INFORMASI PROFIL & AVATAR --}}
    <div class="card p-6">
        <header class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Informasi Profil</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Perbarui foto profil dan data diri Anda.
            </p>
        </header>

        {{-- Form Upload (Wajib enctype multipart) --}}
        <form method="post" action="{{ route('admin.profile.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-8" enctype="multipart/form-data">
            @csrf
            @method('patch')

            {{-- Kolom Kiri: Avatar dengan Preview AlpineJS --}}
            <div class="md:col-span-1 flex flex-col items-center text-center" 
                 x-data="{ photoName: null, photoPreview: null }">
                
                {{-- Input File Tersembunyi --}}
                <input type="file" id="avatar" name="avatar" class="hidden" x-ref="photo"
                       accept="image/jpg,image/jpeg,image/png"
                       x-on:change="
                            const file = $refs.photo.files[0];
                            if (file) {
                                photoName = file.name;
                                const reader = new FileReader();
                                reader.onload = (e) => { photoPreview = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                       ">

                {{-- Area Lingkaran Avatar (Klik untuk ganti) --}}
                <div class="relative group cursor-pointer" x-on:click.prevent="$refs.photo.click()">
                    <div class="w-32 h-32 rounded-full overflow-hidden bg-slate-100 ring-4 ring-white dark:ring-slate-700 shadow-lg mb-4 relative mx-auto">
                        
                        {{-- 1. Tampilan jika ada PREVIEW baru --}}
                        <template x-if="photoPreview">
                            <img :src="photoPreview" class="w-full h-full object-cover">
                        </template>

                        {{-- 2. Tampilan jika TIDAK ada preview (Gambar Lama/Inisial) --}}
                        <template x-if="!photoPreview">
                            <div class="w-full h-full">
                                @if($user->avatar_path)
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="w-full h-full object-cover">
                                @else
                                    {{-- Inisial Nama --}}
                                    <div class="w-full h-full flex items-center justify-center bg-[#0f172a] text-white text-4xl font-bold">
                                        {{ substr($user->full_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        </template>
                        
                        {{-- Overlay Icon Kamera saat Hover --}}
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity transition-all duration-300">
                            <i class="material-icons text-white text-3xl">camera_alt</i>
                        </div>
                    </div>
                </div>
                
                {{-- Pesan Error Avatar --}}
                @error('avatar') 
                    <p class="text-xs text-red-500 font-bold mb-2">{{ $message }}</p> 
                @enderror

                <h3 class="font-bold text-slate-800 dark:text-white text-lg">{{ $user->full_name }}</h3>
                <span class="badge badge-primary mt-2">{{ ucfirst($user->role ?? 'Staff') }}</span>
                <p class="text-xs text-slate-400 mt-2">Klik foto untuk mengganti (Max 2MB)</p>
            </div>

            {{-- Kolom Kanan: Form Input Data Diri --}}
            <div class="md:col-span-2 space-y-5">
                
                {{-- Nama Lengkap --}}
                <div>
                    <label for="full_name" class="form-label label-required">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" class="form-input @error('full_name') is-invalid @enderror" 
                           value="{{ old('full_name', $user->full_name) }}" required autocomplete="name">
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Username & Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="username" class="form-label label-required">Username</label>
                        <input type="text" id="username" name="username" class="form-input @error('username') is-invalid @enderror" 
                               value="{{ old('username', $user->username) }}" required>
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label label-required">Email</label>
                        <input type="email" id="email" name="email" class="form-input @error('email') is-invalid @enderror" 
                               value="{{ old('email', $user->email) }}" required autocomplete="email">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="mt-2 text-sm text-amber-600 bg-amber-50 p-2 rounded">
                                Alamat email Anda belum diverifikasi.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- NIK & Phone --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="nik" class="form-label label-optional">NIK</label>
                        <input type="text" id="nik" name="nik" class="form-input @error('nik') is-invalid @enderror" 
                               value="{{ old('nik', $user->nik) }}">
                        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="phone_number" class="form-label label-optional">No. Telepon / WA</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-input @error('phone_number') is-invalid @enderror" 
                               value="{{ old('phone_number', $user->phone_number) }}">
                        @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Alamat --}}
                <div>
                    <label for="address" class="form-label label-optional">Alamat Lengkap</label>
                    <textarea id="address" name="address" class="form-textarea @error('address') is-invalid @enderror" rows="2">{{ old('address', $user->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons text-[18px] mr-2">save</i> Simpan Profil
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- 2. UPDATE PASSWORD --}}
    <div class="card p-6">
        <header class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Perbarui Password</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Pastikan akun Anda menggunakan password yang kuat agar tetap aman.
            </p>
        </header>

        <form method="post" action="{{ route('admin.profile.password.update') }}" class="max-w-xl">
            @csrf
            @method('put')

            <div class="space-y-5">
                {{-- Password Saat Ini --}}
                <div>
                    <label for="current_password" class="form-label label-required">Password Saat Ini</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="current_password" name="current_password" 
                               class="form-input pr-10 @if($errors->updatePassword->has('current_password')) is-invalid @endif" 
                               autocomplete="current-password">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                            <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                        </button>
                    </div>
                    @if($errors->updatePassword->has('current_password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                    @endif
                </div>

                {{-- Password Baru --}}
                <div>
                    <label for="password" class="form-label label-required">Password Baru</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="password" name="password" 
                               class="form-input pr-10 @if($errors->updatePassword->has('password')) is-invalid @endif" 
                               autocomplete="new-password">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                            <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                        </button>
                    </div>
                    @if($errors->updatePassword->has('password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                    @endif
                </div>

                {{-- Konfirmasi Password --}}
                <div>
                    <label for="password_confirmation" class="form-label label-required">Konfirmasi Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" 
                               class="form-input pr-10 @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" 
                               autocomplete="new-password">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                            <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                        </button>
                    </div>
                    @if($errors->updatePassword->has('password_confirmation'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                    @endif
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn btn-secondary">
                        <i class="material-icons text-[18px] mr-2">lock_reset</i> Update Password
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- 3. HAPUS AKUN (DANGER ZONE) --}}
    <div class="card p-6 border border-rose-100 dark:border-rose-900/30 bg-white dark:bg-slate-800">
        <header class="mb-6 pb-4">
            <h2 class="text-lg font-bold text-rose-600 dark:text-rose-500">Hapus Akun</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Setelah akun Anda dihapus, semua data terkait akan dihapus secara permanen dari server.
            </p>
        </header>

        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-600 dark:text-slate-300 max-w-xl pr-4">
                Tindakan ini tidak dapat dibatalkan. Pastikan Anda telah mengunduh data apa pun yang ingin Anda simpan sebelum melanjutkan.
            </div>
            
            <button class="btn btn-danger whitespace-nowrap" x-data="" 
                    @click="$dispatch('open-modal', 'confirm-user-deletion')">
                Hapus Akun
            </button>
        </div>

        {{-- Modal Konfirmasi Hapus (Custom Modal) --}}
        <div x-data="{ show: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }"
             x-show="show"
             x-on:open-modal.window="if ($event.detail === 'confirm-user-deletion') show = true"
             x-on:keydown.escape.window="show = false"
             class="fixed inset-0 z-[100] overflow-y-auto"
             style="display: none;">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
                 x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="show = false"></div>

            {{-- Modal Content --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 p-6 text-left align-middle shadow-xl transition-all border border-slate-200 dark:border-slate-700"
                     x-show="show"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="material-icons text-rose-500">warning</i>
                        Konfirmasi Hapus Akun
                    </h3>
                    
                    <div class="mt-3">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.
                        </p>
                    </div>

                    <form method="post" action="{{ route('admin.profile.destroy') }}" class="mt-6">
                        @csrf
                        @method('delete')

                        <div>
                            <label for="password_deletion" class="sr-only">Password</label>
                            <input type="password" id="password_deletion" name="password" 
                                   class="form-input @if($errors->userDeletion->has('password')) is-invalid @endif"
                                   placeholder="Masukkan Password Anda" required>
                            
                            @if($errors->userDeletion->has('password'))
                                <div class="invalid-feedback mt-2">
                                    {{ $errors->userDeletion->first('password') }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" class="btn btn-secondary" @click="show = false">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-danger">
                                Ya, Hapus Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script Notifikasi Toast --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('status') === 'profile-updated')
            showToast('Profil dan foto berhasil diperbarui.', 'success');
        @elseif (session('status') === 'password-updated')
            showToast('Password berhasil diperbarui.', 'success');
        @endif
    });
</script>
@endpush
@endsection