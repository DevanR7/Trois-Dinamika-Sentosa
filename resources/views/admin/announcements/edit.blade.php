@extends('admin.layouts.app')

@section('title', 'Edit Pengumuman')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Pengumuman</h1>
            <p class="page-subtitle">Perbarui konten atau target audiens</p>
        </div>
        <div>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST" 
                          x-data="{ type: '{{ old('type', $announcement->type) }}' }">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-4">
                            <label class="form-label">Judul Pengumuman</label>
                            <input type="text" name="title" class="form-input @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $announcement->title) }}">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

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

                        <div class="form-group mb-4" x-show="type === 'targeted'" x-transition>
                            <label class="form-label label-required">Pilih Klien</label>
                            <select name="client_ids[]" class="tom-select" multiple placeholder="Pilih klien...">
                                @foreach($clients as $client)
                                    <option value="{{ $client->client_id }}" 
                                        {{ (collect(old('client_ids', $selectedClientIds))->contains($client->client_id)) ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-6">
                            <label class="form-label label-required">Isi Pengumuman</label>
                            <textarea name="content" class="form-textarea h-48 @error('content') is-invalid @enderror" 
                                      required>{{ old('content', $announcement->content) }}</textarea>
                            @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input w-5 h-5"
                                    {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                                <div>
                                    <span class="font-bold text-sm text-slate-700 dark:text-slate-200">Aktifkan Tayangan</span>
                                    <p class="text-xs text-slate-500">Hapus centang untuk menyembunyikan pengumuman (Draft).</p>
                                </div>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-[18px]">save</i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection