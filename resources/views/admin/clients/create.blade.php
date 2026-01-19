@extends('admin.layouts.app')

@section('title', 'Tambah Klien')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Klien Baru</h1>
            <p class="page-subtitle">Daftarkan pelanggan baru ke dalam sistem</p>
        </div>
        <div>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('admin.clients.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Kartu Kiri: Informasi Akun Login --}}
            <div class="lg:col-span-1">
                <div class="card bg-white dark:bg-slate-800 h-full">
                    <div class="card-header border-l-4 border-l-indigo-500">
                        <h3 class="card-header-title">Informasi Login</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label label-required">Alamat Email</label>
                            <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}" placeholder="email@perusahaan.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Password with Toggle Visibility --}}
                        <div class="form-group" x-data="{ show: false }">
                            <label class="form-label label-required">Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" 
                                       class="form-input pr-10 @error('password') is-invalid @enderror" 
                                       placeholder="Minimal 8 karakter">
                                
                                <button type="button" @click="show = !show" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                                    <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                                </button>
                            </div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group" x-data="{ show: false }">
                            <label class="form-label label-required">Konfirmasi Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password_confirmation" class="form-input pr-10" 
                                       placeholder="Ulangi password">
                                <button type="button" @click="show = !show" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 focus:outline-none">
                                    <i class="material-icons text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-300 mt-4">
                            <div class="flex gap-2">
                                <i class="material-icons text-[16px]">info</i>
                                <p>Klien baru akan berstatus <b>Belum Disetujui</b> secara default. Anda perlu menyetujuinya di halaman index agar mereka bisa login.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu Kanan: Profil Perusahaan --}}
            <div class="lg:col-span-2">
                <div class="card bg-white dark:bg-slate-800">
                    <div class="card-header border-l-4 border-l-emerald-500">
                        <h3 class="card-header-title">Profil Perusahaan/Klien</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <div class="form-group md:col-span-2">
                            <label class="form-label label-required">Nama Klien / Perusahaan</label>
                            <input type="text" name="client_name" class="form-input @error('client_name') is-invalid @enderror" 
                                   value="{{ old('client_name') }}" placeholder="PT. Sukses Makmur">
                            @error('client_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Penanggung Jawab (PIC)</label>
                            <input type="text" name="person_in_charge" class="form-input @error('person_in_charge') is-invalid @enderror" 
                                   value="{{ old('person_in_charge') }}" placeholder="Bpk. Budi">
                            @error('person_in_charge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor Telepon / WA</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-[18px]">phone</i>
                                </div>
                                <input type="text" name="phone_number" class="form-input pl-10 @error('phone_number') is-invalid @enderror" 
                                       value="{{ old('phone_number') }}" placeholder="0812...">
                            </div>
                            @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="address" class="form-textarea" rows="3" 
                                      placeholder="Alamat kantor atau pengiriman...">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                    <div class="card-footer flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50">
                        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons text-[18px]">save</i> Simpan Klien
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection