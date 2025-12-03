@extends('client.layouts.app')

@section('title', 'Edit Profil')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- Header Halaman --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Pengaturan Profil</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola informasi akun dan keamanan Anda.</p>
        </div>
    </div>

    {{-- Alert Error Global (Jika ada error validasi) --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 mt-0.5">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-xs text-red-700 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORMULIR UTAMA (Dibungkus satu form agar tombol submit menangani semuanya) --}}
    <form method="post" action="{{ route('client.profile.update') }}">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KOLOM KIRI: INFORMASI AKUN (Lebar 2/3) --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Kartu: Detail Profil --}}
                <div class="dashboard-card p-6">
                    <div class="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="material-icons">person</i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">Informasi Dasar</h3>
                            <p class="text-xs text-slate-500">Perbarui detail identitas perusahaan/klien.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Nama Klien --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Nama Perusahaan / Klien <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="material-icons text-[18px]">business</i>
                                </span>
                                <input type="text" name="client_name" value="{{ old('client_name', $client->client_name) }}" 
                                       class="form-input pl-10" placeholder="Nama Lengkap Perusahaan" required>
                            </div>
                        </div>

                        {{-- PIC --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Narahubung (PIC)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="material-icons text-[18px]">badge</i>
                                </span>
                                <input type="text" name="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge) }}" 
                                       class="form-input pl-10" placeholder="Nama PIC">
                            </div>
                        </div>

                        {{-- Telepon --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Nomor Telepon / WA
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="material-icons text-[18px]">call</i>
                                </span>
                                <input type="text" name="phone_number" value="{{ old('phone_number', $client->phone_number) }}" 
                                       class="form-input pl-10" placeholder="Contoh: 08123456789">
                            </div>
                        </div>

                        {{-- Alamat --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Alamat Lengkap
                            </label>
                            <div class="relative">
                                <span class="absolute top-3 left-0 flex items-start pl-3 text-slate-400">
                                    <i class="material-icons text-[18px]">place</i>
                                </span>
                                <textarea name="address" class="form-input pl-10 min-h-[100px] py-3" 
                                          placeholder="Alamat lengkap pengiriman/kantor">{{ old('address', $client->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Simpan (Mobile/Tablet Only - agar mudah dijangkau) --}}
                <div class="lg:hidden">
                    <button type="submit" class="w-full btn-primary bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg shadow-lg flex items-center justify-center gap-2">
                        <i class="material-icons">save</i> Simpan Perubahan
                    </button>
                </div>
            </div>

            {{-- KOLOM KANAN: KEAMANAN & PASSWORD (Lebar 1/3) --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Kartu: Keamanan --}}
                <div class="dashboard-card p-6 border-t-4 border-t-amber-500">
                    <div class="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <i class="material-icons">lock</i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">Keamanan</h3>
                            <p class="text-xs text-slate-500">Ganti password akun.</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-300 mb-4 flex gap-2">
                            <i class="material-icons text-[16px]">info</i>
                            <span>Kosongkan kolom di bawah jika Anda tidak ingin mengubah password.</span>
                        </div>

                        {{-- Current Password --}}
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Password Saat Ini
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="current_password" class="form-input pr-10">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <i class="material-icons text-[18px]" x-text="show ? 'visibility' : 'visibility_off'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- New Password --}}
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Password Baru
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" class="form-input pr-10">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <i class="material-icons text-[18px]" x-text="show ? 'visibility' : 'visibility_off'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password_confirmation" class="form-input pr-10">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <i class="material-icons text-[18px]" x-text="show ? 'visibility' : 'visibility_off'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Simpan (Desktop) --}}
                    <div class="mt-8 hidden lg:block">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center gap-2 hover:-translate-y-0.5">
                            <i class="material-icons">save</i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection