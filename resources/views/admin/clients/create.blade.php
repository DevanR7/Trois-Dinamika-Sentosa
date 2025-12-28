@extends('admin.layouts.app')

@section('title', 'Tambah Klien Baru')

@section('content')
    <div class="max-w-4xl mx-auto">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="page-title">Tambah Klien</h2>
                <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition-colors mt-1">
                    <i class="material-icons text-base">arrow_back</i> Kembali ke Daftar
                </a>
            </div>
        </div>

        {{-- Alert Informasi Akun --}}
        <div class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-100 flex items-start gap-3">
            <i class="material-icons text-blue-500 mt-0.5">info</i>
            <div class="text-sm text-blue-700 leading-relaxed">
                <p class="font-bold mb-1">Informasi Akun Login</p>
                Klien dapat menggunakan email dan password ini untuk login ke sistem (Client Area). Status awal akun adalah <strong>Belum Disetujui</strong> (Perlu approval admin setelah dibuat).
            </div>
        </div>

        {{-- Form Card --}}
        <div class="card" x-data="{ 
            showPass: false, 
            password: '', 
            confirm: '',
            get match() { return this.password === this.confirm && this.password.length > 0; },
            get mismatch() { return this.password !== this.confirm && this.confirm.length > 0; }
        }">
            <form action="{{ route('admin.clients.store') }}" method="POST" class="p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    {{-- Nama Klien --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Nama Klien / Perusahaan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">business</i>
                            </span>
                            <input type="text" name="client_name" value="{{ old('client_name') }}" 
                                   class="form-input pl-10 @error('client_name') border-red-500 @enderror" 
                                   placeholder="Nama lengkap atau nama toko..." required>
                        </div>
                        @error('client_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="form-label">Email (Username Login)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">email</i>
                            </span>
                            <input type="email" name="email" value="{{ old('email') }}" 
                                   class="form-input pl-10 @error('email') border-red-500 @enderror" 
                                   placeholder="email@contoh.com">
                        </div>
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- No Telepon --}}
                    <div>
                        <label class="form-label">No. Telepon / WA</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">phone</i>
                            </span>
                            <input type="text" name="phone_number" value="{{ old('phone_number') }}" 
                                   class="form-input pl-10 @error('phone_number') border-red-500 @enderror" 
                                   placeholder="0812...">
                        </div>
                        @error('phone_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- PIC --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Penanggung Jawab (PIC)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">person</i>
                            </span>
                            <input type="text" name="person_in_charge" value="{{ old('person_in_charge') }}" 
                                   class="form-input pl-10" placeholder="Nama orang yang bisa dihubungi">
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Alamat Lengkap</label>
                        <div class="relative">
                            <span class="absolute top-3 left-0 flex items-start pl-3 text-slate-400">
                                <i class="material-icons text-lg">location_on</i>
                            </span>
                            <textarea name="address" rows="3" class="form-input pl-10" placeholder="Alamat pengiriman / penagihan...">{{ old('address') }}</textarea>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2 my-2 border-t border-slate-100 dark:border-slate-700"></div>

                    {{-- Password --}}
                    <div>
                        <label class="form-label">Password Login <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">lock</i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" 
                                   name="password" x-model="password"
                                   class="form-input pl-10 pr-10 @error('password') border-red-500 @enderror" 
                                   placeholder="Minimal 8 karakter" required>
                            
                            {{-- Toggle Eye --}}
                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-indigo-600 cursor-pointer">
                                <i class="material-icons text-lg" x-text="showPass ? 'visibility_off' : 'visibility'"></i>
                            </button>
                        </div>
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div>
                        <label class="form-label">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">lock_reset</i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" 
                                   name="password_confirmation" x-model="confirm"
                                   class="form-input pl-10 pr-10" 
                                   :class="{'border-green-500 focus:ring-green-500': match, 'border-red-500 focus:ring-red-500': mismatch}"
                                   placeholder="Ulangi password" required>
                                   
                            {{-- Icon Check/Error --}}
                            <template x-if="match">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-green-500">
                                    <i class="material-icons text-lg">check_circle</i>
                                </span>
                            </template>
                        </div>
                        {{-- Pesan Error JS --}}
                        <p x-show="mismatch" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                            <i class="material-icons text-[14px]">error</i> Password tidak cocok!
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" :disabled="mismatch">
                        <i class="material-icons text-lg">save</i>
                        Simpan Klien
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection