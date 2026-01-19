@extends('admin.layouts.app')

@section('title', 'Buat Pengumuman')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Buat Pengumuman Baru</h1>
            <p class="page-subtitle">Informasi akan ditampilkan di dashboard klien</p>
        </div>
        <div>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Form Kiri --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    {{-- Alpine Data: type default dari old input atau 'broadcast' --}}
                    <form action="{{ route('admin.announcements.store') }}" method="POST" x-data="{ type: '{{ old('type', 'broadcast') }}' }">
                        @csrf

                        {{-- Judul --}}
                        <div class="form-group mb-4">
                            <label class="form-label">Judul Pengumuman (Opsional)</label>
                            <input type="text" name="title" class="form-input @error('title') is-invalid @enderror" 
                                   value="{{ old('title') }}" placeholder="Contoh: Pemeliharaan Sistem, Promo Akhir Tahun...">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Tipe Pengumuman --}}
                        <div class="form-group mb-4">
                            <label class="form-label label-required">Tipe Distribusi</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Radio Broadcast --}}
                                <label class="relative flex items-center p-4 rounded-xl border cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800"
                                       :class="type === 'broadcast' ? 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" name="type" value="broadcast" class="sr-only" x-model="type">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                            <i class="material-icons">podcasts</i>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Broadcast</span>
                                            <span class="block text-xs text-slate-500">Semua Klien</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-4 right-4" x-show="type === 'broadcast'">
                                        <i class="material-icons text-indigo-500 text-[18px]">check_circle</i>
                                    </div>
                                </label>

                                {{-- Radio Targeted --}}
                                <label class="relative flex items-center p-4 rounded-xl border cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800"
                                       :class="type === 'targeted' ? 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" name="type" value="targeted" class="sr-only" x-model="type">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                            <i class="material-icons">group</i>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Targeted</span>
                                            <span class="block text-xs text-slate-500">Klien Spesifik</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-4 right-4" x-show="type === 'targeted'">
                                        <i class="material-icons text-indigo-500 text-[18px]">check_circle</i>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Pemilihan Klien (Hanya muncul jika Targeted) --}}
                        <div class="form-group mb-4" x-show="type === 'targeted'" x-transition>
                            <label class="form-label label-required">Pilih Klien</label>
                            <select name="client_ids[]" class="tom-select" multiple placeholder="Pilih klien...">
                                @foreach($clients as $client)
                                    <option value="{{ $client->client_id }}" 
                                        {{ (collect(old('client_ids'))->contains($client->client_id)) ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-400 mt-1">Anda dapat memilih lebih dari satu klien.</p>
                            @error('client_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Isi Konten --}}
                        <div class="form-group mb-6">
                            <label class="form-label label-required">Isi Pengumuman</label>
                            <textarea name="content" class="form-textarea h-48 @error('content') is-invalid @enderror" 
                                      placeholder="Tulis pesan pengumuman di sini..." required>{{ old('content') }}</textarea>
                            @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Status Aktif --}}
                        <div class="form-group mb-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input w-5 h-5" checked>
                                <div>
                                    <span class="font-bold text-sm text-slate-700 dark:text-slate-200">Langsung Tayangkan</span>
                                    <p class="text-xs text-slate-500">Jika dicentang, pengumuman akan langsung muncul di portal klien.</p>
                                </div>
                            </label>
                        </div>

                        {{-- Actions --}}
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-[18px]">send</i> Simpan & Publikasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Kanan --}}
        <div class="lg:col-span-1">
            <div class="card bg-blue-50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-800">
                <div class="card-body">
                    <h3 class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                        <i class="material-icons text-[18px]">tips_and_updates</i> Tips Pengumuman
                    </h3>
                    <ul class="space-y-3 text-xs text-blue-700 dark:text-blue-400 leading-relaxed">
                        <li class="flex gap-2">
                            <i class="material-icons text-[14px] mt-0.5">podcasts</i>
                            <span>Gunakan <b>Broadcast</b> untuk informasi umum seperti libur nasional, maintenance server, atau perubahan kebijakan umum.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="material-icons text-[14px] mt-0.5">group</i>
                            <span>Gunakan <b>Targeted</b> untuk informasi spesifik seperti tagihan khusus, kontrak expired, atau promo khusus pelanggan tertentu.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection