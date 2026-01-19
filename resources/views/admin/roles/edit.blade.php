@extends('admin.layouts.app')

@section('title', 'Edit Role')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Edit Role</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Ubah nama role atau sesuaikan hak akses <span class="font-bold text-indigo-600">{{ ucfirst($role->name) }}</span>.
            </p>
        </div>
        
        <div class="flex gap-2">
            @if(in_array($role->name, ['admin', 'superadmin']))
                <span class="badge bg-amber-100 text-amber-700 border-amber-200 flex items-center gap-1">
                    <i class="material-icons text-[14px]">lock</i> System Role
                </span>
            @endif
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" x-data="roleEditForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- Card Nama --}}
                <div class="card p-6">
                    <label class="form-label label-required">Nama Role</label>
                    <input type="text" name="name" 
                           class="form-input @error('name') is-invalid @enderror" 
                           value="{{ old('name', $role->name) }}" 
                           {{ in_array($role->name, ['admin', 'superadmin']) ? 'readonly' : '' }}
                           required>
                    
                    @if(in_array($role->name, ['admin', 'superadmin']))
                        <p class="text-[10px] text-amber-600 mt-1 italic">Nama role sistem tidak dapat diubah untuk menjaga integritas aplikasi.</p>
                    @endif
                    
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Card Permissions --}}
                <div class="card">
                    <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                        <h3 class="card-header-title">Daftar Hak Akses</h3>
                        
                        {{-- Fitur khusus Admin: Select All --}}
                        @if(!in_array($role->name, ['superadmin'])) 
                            <button type="button" @click="toggleAllGlobal()" class="text-xs font-bold text-indigo-600 hover:underline">
                                Toggle Semua
                            </button>
                        @endif
                    </div>
                    
                    <div class="card-body bg-slate-50/50 dark:bg-slate-900/20">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            
                            @foreach($permissions as $groupName => $perms)
                                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm h-full flex flex-col">
                                    
                                    {{-- Group Header --}}
                                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                                        <span class="font-bold text-sm text-slate-700 dark:text-slate-200 capitalize">
                                            {{ $groupName }}
                                        </span>
                                        {{-- Hide select all for superadmin (always all) --}}
                                        @if($role->name !== 'superadmin')
                                            <label class="inline-flex items-center cursor-pointer" title="Pilih grup ini">
                                                <input type="checkbox" class="form-checkbox rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 w-4 h-4"
                                                    @change="toggleGroup('{{ $groupName }}', $event.target.checked)">
                                            </label>
                                        @endif
                                    </div>

                                    <div class="space-y-2 flex-1">
                                        @foreach($perms as $perm)
                                            <label class="flex items-start gap-2 cursor-pointer group hover:bg-slate-50 dark:hover:bg-slate-700/50 p-1 -mx-1 rounded transition-colors">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                                       {{ in_array($perm->name, $roleHasPermissions) ? 'checked' : '' }}
                                                       class="perm-checkbox-{{ $groupName }} mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 w-4 h-4 transition-all"
                                                       {{ $role->name === 'superadmin' ? 'disabled checked' : '' }}> 
                                                       {{-- Superadmin locked to checked --}}
                                                
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

            {{-- KOLOM KANAN --}}
            <div class="xl:col-span-3 space-y-6">
                
                {{-- Card Info --}}
                <div class="card p-5 bg-white dark:bg-slate-800">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Statistik</h4>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-slate-600 dark:text-slate-300">Total User</span>
                        <span class="font-bold text-slate-800 dark:text-white">{{ $role->users()->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-300">Total Permission</span>
                        <span class="font-bold text-indigo-600">{{ count($roleHasPermissions) }}</span>
                    </div>
                </div>

                {{-- Sticky Action --}}
                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    
                    @if($role->name === 'superadmin')
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 rounded-lg text-xs text-amber-700 dark:text-amber-400 mb-3">
                            Role Superadmin memiliki akses penuh otomatis (Bypass). Anda tidak perlu mengubah permissions.
                        </div>
                    @else
                        <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3">
                            <i class="material-icons text-[18px]">save</i>
                            Simpan Perubahan
                        </button>
                    @endif

                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary w-full justify-center">
                        Batal
                    </a>
                </div>

            </div>

        </div>
    </form>

    @push('scripts')
    <script>
        function roleEditForm() {
            return {
                toggleGroup(groupName, isChecked) {
                    const checkboxes = document.querySelectorAll(`.perm-checkbox-${groupName}`);
                    checkboxes.forEach(cb => {
                        if(!cb.disabled) cb.checked = isChecked;
                    });
                },

                toggleAllGlobal() {
                    const allCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
                    let allChecked = true;
                    // Cek status checkbox yang visible/enabled
                    const enabledCheckboxes = Array.from(allCheckboxes).filter(cb => !cb.disabled);

                    enabledCheckboxes.forEach(cb => {
                        if (!cb.checked) allChecked = false;
                    });

                    enabledCheckboxes.forEach(cb => {
                        cb.checked = !allChecked;
                    });
                }
            }
        }
    </script>
    @endpush

@endsection