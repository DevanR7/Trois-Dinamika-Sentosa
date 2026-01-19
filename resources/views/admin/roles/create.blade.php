@extends('admin.layouts.app')

@section('title', 'Buat Role Baru')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Buat Role Baru</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Tentukan nama role dan hak akses yang dimiliki.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Batal</span>
        </a>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" x-data="roleForm()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI: FORM & PERMISSION (9 Kolom) --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- Card Nama Role --}}
                <div class="card p-6">
                    <label class="form-label label-required">Nama Role</label>
                    <input type="text" name="name" 
                           class="form-input @error('name') is-invalid @enderror" 
                           placeholder="Contoh: Supervisor, Kasir, Staff Gudang" 
                           value="{{ old('name') }}" required>
                    <p class="text-[10px] text-slate-400 mt-1">Gunakan huruf kecil dan tanpa spasi untuk standar sistem (opsional).</p>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Card Permissions Matrix --}}
                <div class="card">
                    <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                        <h3 class="card-header-title">Daftar Hak Akses (Permissions)</h3>
                        <button type="button" @click="toggleAllGlobal()" class="text-xs font-bold text-indigo-600 hover:underline">
                            Pilih Semua
                        </button>
                    </div>
                    
                    <div class="card-body bg-slate-50/50 dark:bg-slate-900/20">
                        {{-- Grid Layout untuk Group Permission --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            
                            @foreach($permissions as $groupName => $perms)
                                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm h-full flex flex-col">
                                    
                                    {{-- Group Header dengan Select All --}}
                                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                                        <span class="font-bold text-sm text-slate-700 dark:text-slate-200 capitalize">
                                            {{ $groupName }}
                                        </span>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="form-checkbox rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 w-4 h-4"
                                                   @change="toggleGroup('{{ $groupName }}', $event.target.checked)">
                                        </label>
                                    </div>

                                    {{-- List Item --}}
                                    <div class="space-y-2 flex-1">
                                        @foreach($perms as $perm)
                                            <label class="flex items-start gap-2 cursor-pointer group hover:bg-slate-50 dark:hover:bg-slate-700/50 p-1 -mx-1 rounded transition-colors">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                                       class="perm-checkbox-{{ $groupName }} mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 w-4 h-4 transition-all">
                                                <span class="text-xs text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white leading-tight break-words">
                                                    {{ $perm->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: AKSI (3 Kolom) --}}
            <div class="xl:col-span-3 space-y-6">
                
                {{-- Card Info --}}
                <div class="card p-5 bg-indigo-50 dark:bg-indigo-900/20 border-indigo-100 dark:border-indigo-800">
                    <h4 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 mb-2 flex items-center gap-2">
                        <i class="material-icons text-[18px]">info</i> Petunjuk
                    </h4>
                    <p class="text-xs text-indigo-700 dark:text-indigo-200 leading-relaxed">
                        Centang hak akses yang ingin diberikan kepada Role ini. 
                        Pengguna dengan role ini hanya bisa mengakses menu dan fitur yang dicentang.
                    </p>
                </div>

                {{-- Sticky Action --}}
                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3">
                        <i class="material-icons text-[18px]">save</i>
                        Simpan Role
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary w-full justify-center">
                        Batal
                    </a>
                </div>

            </div>

        </div>
    </form>

    {{-- Script Alpine untuk Select All --}}
    @push('scripts')
    <script>
        function roleForm() {
            return {
                // Toggle semua checkbox dalam satu grup
                toggleGroup(groupName, isChecked) {
                    const checkboxes = document.querySelectorAll(`.perm-checkbox-${groupName}`);
                    checkboxes.forEach(cb => {
                        cb.checked = isChecked;
                    });
                },

                // Toggle semua checkbox di halaman (Global)
                toggleAllGlobal() {
                    const allCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
                    // Cek apakah ada yang belum dicentang
                    let allChecked = true;
                    allCheckboxes.forEach(cb => {
                        if (!cb.checked) allChecked = false;
                    });

                    // Jika semua sudah dicentang, maka uncheck semua. Jika belum, check semua.
                    allCheckboxes.forEach(cb => {
                        cb.checked = !allChecked;
                    });
                    
                    // Reset group headers checkboxes (visual only)
                    const groupHeaders = document.querySelectorAll('input[type="checkbox"]:not([name="permissions[]"])');
                    groupHeaders.forEach(cb => cb.checked = !allChecked);
                }
            }
        }
    </script>
    @endpush

@endsection