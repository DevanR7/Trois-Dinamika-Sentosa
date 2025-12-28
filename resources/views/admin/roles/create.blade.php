@extends('admin.layouts.app')

@section('title', 'Buat Role Baru')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Buat Role Baru</h1>
                <p class="page-subtitle">Tentukan nama role dan pilih hak akses yang diizinkan.</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf

            {{-- 1. Role Name --}}
            <div class="card mb-6">
                <div class="card-body">
                    <div class="max-w-xl">
                        <label class="form-label label-required mb-1">Nama Role</label>
                        <input type="text" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               placeholder="Contoh: staff_gudang" 
                               value="{{ old('name') }}" required>
                        
                        {{-- Hint Text Rapi --}}
                        <div class="mt-1.5 flex items-start gap-1 text-xs text-slate-500 dark:text-slate-400">
                            <i class="material-icons text-[14px] text-slate-400">info</i>
                            <span class="leading-tight">Gunakan huruf kecil dan garis bawah (snake_case). Contoh: <code>supervisor_sales</code></span>
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
                                                   class="form-check-input perm-item group-{{ $group }} w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
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
                    <i class="material-icons text-sm mr-2">save</i> Simpan Role
                </button>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Group Select All Logic
        document.querySelectorAll('.group-checkbox').forEach(groupCheck => {
            groupCheck.addEventListener('change', function() {
                const groupName = this.dataset.group;
                const isChecked = this.checked;
                
                document.querySelectorAll(`.perm-item.group-${groupName}`).forEach(item => {
                    item.checked = isChecked;
                });
            });
        });

        // 2. Global Select All Logic
        const checkAllBtn = document.getElementById('checkAllBtn');
        let allChecked = false;

        checkAllBtn.addEventListener('click', function() {
            allChecked = !allChecked;
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = allChecked;
            });
            this.textContent = allChecked ? 'Batal Pilih Semua' : 'Pilih Semua Akses';
        });

    });
</script>
@endpush