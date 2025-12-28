@extends('client.layouts.app')

@section('title', 'Profil Saya')

@section('content')

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Pengaturan Akun</h1>
            <p class="page-subtitle">Kelola informasi perusahaan dan keamanan akun Anda.</p>
        </div>
    </div>

    {{-- Alert jika diarahkan paksa karena profil belum lengkap --}}
    @if(session('info'))
        <div class="p-4 mb-6 text-sm text-blue-800 rounded-xl bg-blue-50 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800 flex items-start gap-3" role="alert">
            <i class="material-icons text-base">info</i>
            <div>
                <span class="font-bold">Perhatian:</span> {{ session('info') }}
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- LEFT COLUMN: Profile Card --}}
        <div class="lg:col-span-1">
            <div class="card sticky top-24">
                <div class="card-body text-center flex flex-col items-center">
                    
                    {{-- Avatar --}}
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-slate-800 to-[#0f172a] text-white flex items-center justify-center text-3xl font-bold shadow-lg ring-4 ring-slate-50 dark:ring-slate-700 mb-4">
                        {{ substr($client->client_name, 0, 1) }}
                    </div>

                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">
                        {{ $client->client_name }}
                    </h3>
                    
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-1">
                        <i class="material-icons text-[14px]">email</i>
                        {{ $client->email }}
                    </p>

                    {{-- Status Badge --}}
                    <div class="flex gap-2 justify-center mb-6">
                        @if($client->is_approved)
                            <span class="badge badge-success badge-pill">
                                <i class="material-icons text-[12px] mr-1">verified</i> Terverifikasi
                            </span>
                        @else
                            <span class="badge badge-warning badge-pill">Menunggu Persetujuan</span>
                        @endif
                    </div>

                    <div class="w-full border-t border-slate-100 dark:border-slate-700/50 pt-4 text-left">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Informasi Sistem</p>
                        <div class="flex justify-between items-center py-2 text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Bergabung</span>
                            <span class="font-medium text-slate-800 dark:text-white">{{ $client->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Login Terakhir</span>
                            <span class="font-medium text-slate-800 dark:text-white">Hari ini</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Edit Form --}}
        <div class="lg:col-span-2">
            <div class="card">
                <form action="{{ route('client.profile.update') }}" method="POST" class="flex flex-col h-full">
                    @csrf
                    @method('PATCH')

                    {{-- Section 1: Informasi Umum --}}
                    <div class="card-header bg-transparent pb-0 border-b-0">
                        <h3 class="card-header-title text-sm flex items-center gap-2">
                            <i class="material-icons text-slate-400">business</i>
                            Informasi Perusahaan & Kontak
                        </h3>
                    </div>
                    
                    <div class="card-body grid gap-6">
                        {{-- Email (Read Only) --}}
                        <div>
                            <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-white uppercase tracking-wider">
                                Email (Login)
                            </label>
                            <input type="email" value="{{ $client->email }}" disabled 
                                class="bg-slate-100 border border-slate-200 text-slate-500 text-sm rounded-lg focus:ring-0 focus:border-slate-200 block w-full p-2.5 cursor-not-allowed dark:bg-slate-700 dark:border-slate-600 dark:text-slate-400">
                            <p class="mt-1 text-[10px] text-slate-400">Email tidak dapat diubah. Hubungi admin untuk perubahan email.</p>
                        </div>

                        {{-- Nama Perusahaan/Klien --}}
                        <div>
                            <label for="client_name" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                Nama Perusahaan / Klien <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="client_name" name="client_name" value="{{ old('client_name', $client->client_name) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition-colors @error('client_name') border-red-500 @enderror">
                            @error('client_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- PIC --}}
                            <div>
                                <label for="person_in_charge" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                    PIC (Penanggung Jawab) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <i class="material-icons text-slate-400 text-sm">person</i>
                                    </div>
                                    <input type="text" id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge) }}"
                                        class="ps-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white transition-colors @error('person_in_charge') border-red-500 @enderror">
                                </div>
                                @error('person_in_charge')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- No HP --}}
                            <div>
                                <label for="phone_number" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                    No. Telepon / WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <i class="material-icons text-slate-400 text-sm">phone</i>
                                    </div>
                                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $client->phone_number) }}"
                                        class="ps-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white transition-colors @error('phone_number') border-red-500 @enderror">
                                </div>
                                @error('phone_number')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label for="address" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                Alamat Lengkap <span class="text-red-500">*</span>
                            </label>
                            <textarea id="address" name="address" rows="3"
                                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition-colors @error('address') border-red-500 @enderror">{{ old('address', $client->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-700/50 my-2"></div>

                    {{-- Section 2: Keamanan (Ganti Password) --}}
                    <div class="card-header bg-transparent pb-0 border-b-0 pt-4">
                        <h3 class="card-header-title text-sm flex items-center gap-2 text-amber-600 dark:text-amber-500">
                            <i class="material-icons">lock</i>
                            Ganti Password (Opsional)
                        </h3>
                    </div>

                    <div class="card-body grid gap-6 pt-2">
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-100 dark:border-amber-900/20 text-xs text-amber-800 dark:text-amber-400">
                            Kosongkan bagian ini jika Anda tidak ingin mengubah password akun Anda.
                        </div>

                        <div>
                            <label for="current_password" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                Password Saat Ini
                            </label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white transition-colors @error('current_password') border-red-500 @enderror">
                            @error('current_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                    Password Baru
                                </label>
                                <input type="password" id="password" name="password" autocomplete="new-password"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white transition-colors @error('password') border-red-500 @enderror">
                                @error('password')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block mb-2 text-sm font-medium text-slate-900 dark:text-white">
                                    Konfirmasi Password Baru
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white transition-colors">
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-slate-50 dark:bg-slate-800/50 flex flex-col sm:flex-row justify-end gap-3 mt-auto">
                        <a href="{{ route('client.dashboard') }}" class="btn btn-secondary order-2 sm:order-1">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary order-1 sm:order-2">
                            <i class="material-icons text-sm">save</i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection