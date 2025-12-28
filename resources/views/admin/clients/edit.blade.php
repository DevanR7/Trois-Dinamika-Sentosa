@extends('admin.layouts.app')

@section('title', 'Edit Data Klien')

@section('content')
    <div class="max-w-4xl mx-auto">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="page-title">Edit Klien</h2>
                <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition-colors mt-1">
                    <i class="material-icons text-base">arrow_back</i> Kembali ke Daftar
                </a>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="card" x-data="{ showPass: false, password: '', confirm: '', get mismatch() { return this.password !== this.confirm && this.confirm.length > 0; } }">
            <form action="{{ route('admin.clients.update', $client->client_id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {{-- Nama Klien --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Nama Klien / Perusahaan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">business</i>
                            </span>
                            <input type="text" name="client_name" value="{{ old('client_name', $client->client_name) }}" 
                                   class="form-input pl-10 @error('client_name') border-red-500 @enderror" required>
                        </div>
                        @error('client_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="form-label">Email (Opsional)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">email</i>
                            </span>
                            <input type="email" name="email" value="{{ old('email', $client->email) }}" 
                                   class="form-input pl-10 @error('email') border-red-500 @enderror">
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
                            <input type="text" name="phone_number" value="{{ old('phone_number', $client->phone_number) }}" 
                                   class="form-input pl-10 @error('phone_number') border-red-500 @enderror">
                        </div>
                    </div>

                    {{-- PIC --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Penanggung Jawab (PIC)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="material-icons text-lg">person</i>
                            </span>
                            <input type="text" name="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge) }}" 
                                   class="form-input pl-10">
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="form-label">Alamat Lengkap</label>
                        <div class="relative">
                            <span class="absolute top-3 left-0 flex items-start pl-3 text-slate-400">
                                <i class="material-icons text-lg">location_on</i>
                            </span>
                            <textarea name="address" rows="3" class="form-input pl-10">{{ old('address', $client->address) }}</textarea>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2 my-2 border-t border-slate-100 dark:border-slate-700"></div>

                    {{-- Ganti Password (Opsional) --}}
                    <div class="col-span-1 md:col-span-2 bg-slate-50 dark:bg-slate-800 p-4 rounded-lg border border-slate-100 dark:border-slate-700">
                        <div class="flex items-start gap-3">
                            <i class="material-icons text-slate-400 mt-1">lock_clock</i>
                            <div>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Ubah Password Login</p>
                                <p class="text-xs text-slate-500 mb-3">Klien dapat menggunakan email dan password ini untuk login. Biarkan kosong jika tidak ingin mengubah password.</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-xs">Password Baru</label>
                                        <div class="relative">
                                            <input :type="showPass ? 'text' : 'password'" name="password" x-model="password"
                                                   class="form-input pr-10" placeholder="********">
                                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-indigo-600">
                                                <i class="material-icons text-lg" x-text="showPass ? 'visibility_off' : 'visibility'"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label text-xs">Konfirmasi Password Baru</label>
                                        <input :type="showPass ? 'text' : 'password'" name="password_confirmation" x-model="confirm"
                                               class="form-input" 
                                               :class="{'border-red-500 focus:ring-red-500': mismatch}"
                                               placeholder="********">
                                        <p x-show="mismatch" class="text-red-500 text-[10px] mt-1">Password tidak cocok!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" :disabled="mismatch">
                        <i class="material-icons text-lg">save</i>
                        Update Klien
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection