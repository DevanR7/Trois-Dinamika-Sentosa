@extends('admin.layouts.app')

@section('title', 'Edit Role')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Role: {{ $role->name }}</h1>
                <p class="page-subtitle">Perbarui nama role dan sesuaikan hak akses.</p>
            </div>
            <div class="flex gap-3">
                @if(!in_array($role->name, ['admin', 'superadmin']))
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                        <i class="material-icons text-sm mr-1">delete</i> Hapus
                    </button>
                @endif
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- 1. Role Name --}}
            <div class="card mb-6">
                <div class="card-body">
                    <div class="max-w-xl">
                        <label class="form-label label-required mb-1">Nama Role</label>
                        <input type="text" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               value="{{ old('name', $role->name) }}" required
                               {{ in_array($role->name, ['admin', 'superadmin']) ? 'readonly' : '' }}>
                        
                        <div class="mt-1.5 flex items-start gap-1 text-xs text-slate-500 dark:text-slate-400">
                            <i class="material-icons text-[14px] text-slate-400">info</i>
                            @if(in_array($role->name, ['admin', 'superadmin']))
                                <span class="leading-tight text-amber-600 font-medium">Nama role sistem inti tidak dapat diubah.</span>
                            @else
                                <span class="leading-tight">Gunakan huruf kecil dan garis bawah (snake_case).</span>
                            @endif
                        </div>
                        
                        @error('name') <div class="invalid-feedback mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- 2. Permissions Grid --}}
            <div class="mb-4 flex items-center justify-between">
                <h3 class="section-title mb-0">Hak Akses (Permissions)</h3>
                <button type="button" id="checkAllBtn" class="text-sm text-indigo-600 hover:underline font-medium hover:text-indigo-800 transition-colors">
                    Pilih Semua Akses
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($permissions as $group => $perms)
                    <div class="card h-full flex flex-col border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="card-header py-3 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center border-b border-slate-100 dark:border-slate-700">
                            <span class="font-bold text-slate-700 dark:text-slate-200 capitalize text-sm">
                                {{ str_replace('-', ' ', $group) }}
                            </span>
                            {{-- Group Check All --}}
                            <label class="inline-flex items-center cursor-pointer" title="Pilih semua di grup ini">
                                <input type="checkbox" class="form-check-input group-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-group="{{ $group }}">
                            </label>
                        </div>
                        <div class="card-body p-4 flex-1">
                            <div class="space-y-3">
                                @foreach($perms as $permission)
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="perm_{{ $permission->id }}" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->name }}" 
                                                   type="checkbox" 
                                                   class="form-check-input perm-item group-{{ $group }} w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                   {{ in_array($permission->name, $roleHasPermissions) ? 'checked' : '' }}>
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="perm_{{ $permission->id }}" class="font-medium text-slate-600 dark:text-slate-300 cursor-pointer hover:text-indigo-600 transition-colors select-none">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg px-8">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        {{-- Hidden Delete Form --}}
        @if(!in_array($role->name, ['admin', 'superadmin']))
            <form id="deleteForm" action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Group Select All Logic
        document.querySelectorAll('.group-checkbox').forEach(groupCheck => {
            const groupName = groupCheck.dataset.group;
            
            // Check if all items in this group are initially checked
            const allItems = document.querySelectorAll(`.perm-item.group-${groupName}`);
            const checkedItems = document.querySelectorAll(`.perm-item.group-${groupName}:checked`);
            
            // If all items are checked, make group checkbox checked
            if(allItems.length > 0 && allItems.length === checkedItems.length) {
                groupCheck.checked = true;
            }

            groupCheck.addEventListener('change', function() {
                const isChecked = this.checked;
                allItems.forEach(item => item.checked = isChecked);
            });
        });

        // 2. Global Select All Logic
        const checkAllBtn = document.getElementById('checkAllBtn');
        let allChecked = false; // Simple toggle logic

        checkAllBtn.addEventListener('click', function() {
            allChecked = !allChecked;
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = allChecked;
            });
            this.textContent = allChecked ? 'Batal Pilih Semua' : 'Pilih Semua Akses';
        });
    });

    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Role?',
            text: "Role ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush