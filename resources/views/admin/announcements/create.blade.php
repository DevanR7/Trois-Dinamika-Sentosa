@extends('admin.layouts.app')

@section('title', 'Buat Pengumuman')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Buat Pengumuman</h1>
                <p class="page-subtitle">Kirim informasi penting kepada klien.</p>
            </div>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.announcements.store') }}" method="POST">
            @csrf

            <div class="card" x-data="{ type: '{{ old('type', 'broadcast') }}' }">
                <div class="card-header">
                    <h3 class="card-header-title">Formulir Pesan</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Judul --}}
                    <div>
                        <label class="form-label label-optional">Judul Pengumuman</label>
                        <input type="text" name="title" 
                               class="form-input font-bold @error('title') is-invalid @enderror" 
                               placeholder="Contoh: Pemeliharaan Sistem, Promo Lebaran" 
                               value="{{ old('title') }}">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tipe Pengumuman --}}
                    <div>
                        <label class="form-label label-required">Tipe Distribusi</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all hover:bg-slate-50"
                                   :class="type === 'broadcast' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200'">
                                <input type="radio" name="type" value="broadcast" class="sr-only" x-model="type">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                        <i class="material-icons">podcasts</i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-700">Broadcast</span>
                                        <span class="block text-xs text-slate-500">Semua klien akan melihat ini.</span>
                                    </div>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all hover:bg-slate-50"
                                   :class="type === 'targeted' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200'">
                                <input type="radio" name="type" value="targeted" class="sr-only" x-model="type">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                        <i class="material-icons">person_search</i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-700">Targeted</span>
                                        <span class="block text-xs text-slate-500">Hanya klien tertentu.</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Target Clients (Visible only if Targeted) --}}
                    <div x-show="type === 'targeted'" x-transition class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="form-label label-required">Pilih Klien Target</label>
                        <select name="client_ids[]" class="tom-select" multiple placeholder="Cari nama klien...">
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}" {{ in_array($client->client_id, old('client_ids', [])) ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Isi Konten --}}
                    <div>
                        <label class="form-label label-required">Isi Pesan</label>
                        <textarea name="content" class="form-textarea h-40" 
                                  placeholder="Tuliskan isi pengumuman lengkap di sini..." required>{{ old('content') }}</textarea>
                        @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                        <div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Publikasikan Sekarang?</span>
                            <p class="text-xs text-slate-500">Jika tidak dicentang, akan disimpan sebagai Draft (Hidden).</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">send</i> Simpan & Kirim
                </button>
            </div>

        </form>
    </div>

@endsection