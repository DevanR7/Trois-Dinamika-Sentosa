@extends('admin.layouts.app')

@section('title', 'Profil Saya')

@section('content')
    {{-- Main Wrapper dengan Alpine.js untuk Tab Management --}}
    {{-- Default tab: 'overview' agar user melihat ringkasan dulu (aman) --}}
    <div class="max-w-5xl mx-auto flex flex-col gap-8" x-data="{ activeTab: 'overview' }">
        
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Akun Saya</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola informasi identitas dan keamanan akun Anda.</p>
            </div>
        </div>

        {{-- TAB NAVIGATION --}}
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                {{-- Tab 1: Ringkasan --}}
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="material-icons text-[18px] mr-2" 
                       :class="activeTab === 'overview' ? 'text-indigo-500' : 'text-slate-400 group-hover:text-slate-500'">badge</i>
                    Ringkasan Profil
                </button>

                {{-- Tab 2: Edit Data --}}
                <button @click="activeTab = 'edit'" 
                        :class="activeTab === 'edit' 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="material-icons text-[18px] mr-2" 
                       :class="activeTab === 'edit' ? 'text-indigo-500' : 'text-slate-400 group-hover:text-slate-500'">edit_note</i>
                    Edit Data Diri
                </button>

                {{-- Tab 3: Keamanan --}}
                <button @click="activeTab = 'security'" 
                        :class="activeTab === 'security' 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="material-icons text-[18px] mr-2" 
                       :class="activeTab === 'security' ? 'text-indigo-500' : 'text-slate-400 group-hover:text-slate-500'">security</i>
                    Keamanan
                </button>
            </nav>
        </div>

        {{-- CONTENT AREA --}}
        <div class="min-h-[400px]">

            {{-- 1. TAB OVERVIEW (RINGKASAN) --}}
            <div x-show="activeTab === 'overview'" x-transition:enter.duration.300ms class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- ID Card Style --}}
                <div class="lg:col-span-1">
                    <div class="card p-6 flex flex-col items-center text-center h-full">
                        <div class="w-32 h-32 rounded-full bg-indigo-50 dark:bg-slate-700 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4 ring-4 ring-white dark:ring-slate-800 shadow-lg">
                            <span class="text-4xl font-bold">{{ substr($user->full_name, 0, 1) }}</span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ $user->full_name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 mt-2 capitalize">
                            {{ $user->getRoleNames()->first() ?? 'Staff' }}
                        </span>
                        <p class="text-xs text-slate-500 mt-4">Bergabung sejak {{ $user->created_at->format('d M Y') }}</p>
                    </div>
                </div>

                {{-- Detail Info Read-Only --}}
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header bg-slate-50 dark:bg-slate-900/50">
                            <h3 class="card-header-title">Detail Informasi</h3>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                                <div>
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Username</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ $user->username }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Email</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                        {{ $user->email }}
                                        @if($user->hasVerifiedEmail())
                                            <i class="material-icons text-[14px] text-emerald-500" title="Terverifikasi">verified</i>
                                        @else
                                            <i class="material-icons text-[14px] text-amber-500" title="Belum Verifikasi">warning</i>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">NIK</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 font-mono">
                                        {{ $user->nik ?? '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">No. Telepon</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ $user->phone_number ?? '-' }}
                                    </p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Alamat</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                        {{ $user->address ?? 'Alamat belum dilengkapi.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                                <button @click="activeTab = 'edit'" class="btn btn-secondary btn-sm">
                                    <i class="material-icons text-sm">edit</i> Ubah Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. TAB EDIT PROFILE (FORM) --}}
            <div x-show="activeTab === 'edit'" x-transition:enter.duration.300ms style="display: none;">
                <form method="post" action="{{ route('admin.profile.update') }}" class="card p-6 sm:p-8 max-w-3xl mx-auto">
                    @csrf
                    @method('patch')

                    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-slate-100 dark:border-slate-700">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                            <i class="material-icons text-[20px]">manage_accounts</i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Edit Informasi Pribadi</h2>
                            <p class="text-xs text-slate-500">Perubahan akan langsung diterapkan.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label class="form-label">Nama Lengkap</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-[18px]">badge</i>
                                </div>
                                <input type="text" name="full_name" class="form-input pl-10" value="{{ old('full_name', $user->full_name) }}" required>
                            </div>
                            @error('full_name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-[18px]">alternate_email</i>
                                </div>
                                <input type="text" name="username" class="form-input pl-10 bg-slate-50 dark:bg-slate-900" value="{{ old('username', $user->username) }}" required>
                            </div>
                            @error('username') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-[18px]">mail</i>
                                </div>
                                <input type="email" name="email" class="form-input pl-10" value="{{ old('email', $user->email) }}" required>
                            </div>
                            @error('email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-input" value="{{ old('nik', $user->nik) }}">
                            @error('nik') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number', $user->phone_number) }}">
                            @error('phone_number') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" rows="3" class="form-textarea">{{ old('address', $user->address) }}</textarea>
                            @error('address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="activeTab = 'overview'" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary px-6">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            {{-- 3. TAB SECURITY (PASSWORD & DELETE) --}}
            <div x-show="activeTab === 'security'" x-transition:enter.duration.300ms style="display: none;" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- Password Section --}}
                <div class="lg:col-span-2">
                    <form method="post" action="{{ route('admin.profile.password.update') }}" class="card p-6"
                        x-data="{ 
                            showOld: false, showNew: false, showConfirm: false,
                            newPass: '', confirmPass: ''
                        }">
                        @csrf
                        @method('put')

                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                <i class="material-icons text-[20px]">lock_reset</i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">Ganti Password</h2>
                                <p class="text-xs text-slate-500">Pastikan password Anda kuat dan unik.</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="form-label">Password Saat Ini</label>
                                <div class="relative">
                                    <input :type="showOld ? 'text' : 'password'" name="current_password" class="form-input pr-10 @error('current_password', 'updatePassword') border-red-500 @enderror">
                                    <button type="button" @click="showOld = !showOld" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400">
                                        <i class="material-icons text-[20px]" x-text="showOld ? 'visibility' : 'visibility_off'"></i>
                                    </button>
                                </div>
                                @error('current_password', 'updatePassword') 
                                    <p class="form-error flex items-center gap-1 mt-1"><i class="material-icons text-[14px]">error</i> {{ $message }}</p> 
                                @enderror
                            </div>

                            <div>
                                <label class="form-label">Password Baru</label>
                                <div class="relative">
                                    <input :type="showNew ? 'text' : 'password'" name="password" x-model="newPass" class="form-input pr-10">
                                    <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400">
                                        <i class="material-icons text-[20px]" x-text="showNew ? 'visibility' : 'visibility_off'"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Konfirmasi Password</label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" x-model="confirmPass" 
                                           class="form-input pr-10" :class="{ 'border-red-500 ring-1 ring-red-500': confirmPass && newPass !== confirmPass }">
                                    <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400">
                                        <i class="material-icons text-[20px]" x-text="showConfirm ? 'visibility' : 'visibility_off'"></i>
                                    </button>
                                </div>
                                <p x-show="confirmPass && newPass !== confirmPass" class="text-xs text-red-500 mt-1 flex items-center gap-1 font-medium">
                                    <i class="material-icons text-[14px]">close</i> Password tidak cocok!
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                            <button type="submit" class="btn btn-secondary" :disabled="newPass !== confirmPass || newPass === ''" :class="{ 'opacity-50': newPass !== confirmPass || newPass === '' }">Update Password</button>
                        </div>
                    </form>
                </div>

                {{-- Danger Zone --}}
                <div class="lg:col-span-1">
                    <div class="card p-6 border-red-100 dark:border-red-900/30 bg-red-50/30 dark:bg-red-900/10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/40 flex items-center justify-center text-red-600 dark:text-red-400">
                                <i class="material-icons text-[20px]">warning</i>
                            </div>
                            <h2 class="text-base font-bold text-red-700 dark:text-red-400">Hapus Akun</h2>
                        </div>
                        
                        <p class="text-xs text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">
                            Tindakan ini bersifat <strong>permanen</strong>. Setelah akun dihapus, Anda tidak dapat memulihkannya kembali.
                        </p>

                        <button x-data="" 
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" 
                            class="btn btn-danger-solid w-full justify-center text-sm py-2.5">
                            Hapus Akun Saya
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL HAPUS AKUN --}}
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('admin.profile.destroy') }}" class="p-6" x-data="{ showPass: false }">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="material-icons text-red-500">warning</i> Konfirmasi Penghapusan
            </h2>

            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Masukkan password Anda untuk mengonfirmasi bahwa Anda benar-benar ingin menghapus akun ini.
            </p>

            <div class="mt-6">
                <label for="password" class="sr-only">Password</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" id="password" name="password" class="form-input w-full pr-10" placeholder="Password Anda">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400">
                        <i class="material-icons text-[20px]" x-text="showPass ? 'visibility' : 'visibility_off'"></i>
                    </button>
                </div>
                @error('password', 'userDeletion') <p class="form-error mt-2">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger-solid">Hapus Permanen</button>
            </div>
        </form>
    </x-modal>

    {{-- Script untuk Auto-Switch Tab jika ada Error --}}
    @if($errors->any())
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('profileTabs', () => ({
                    activeTab: '{{ $errors->hasBag('updatePassword') || $errors->hasBag('userDeletion') ? 'security' : 'edit' }}',
                }))
            })
        </script>
    @endif
@endsection