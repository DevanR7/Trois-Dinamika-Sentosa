@extends('admin.layouts.app')

@section('title', 'Manajemen Role & Akses')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Manajemen Role</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Atur hak akses pengguna (Permissions) berdasarkan Role.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            @can('manage-roles')
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px]">add_moderator</i>
                    <span>Buat Role Baru</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- 2. LIST DATA --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th class="w-48">Nama Role</th>
                        <th>Permissions (Hak Akses)</th>
                        <th class="text-center w-32">Users</th>
                        <th class="text-right sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/50 backdrop-blur-sm w-28 px-4">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $index => $role)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-400 text-xs">
                                {{ $roles->firstItem() + $index }}
                            </td>

                            {{-- Nama Role --}}
                            <td class="align-top pt-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2">
                                        <i class="material-icons text-[16px] text-indigo-500">verified_user</i>
                                        {{ ucfirst($role->name) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 mt-1 font-mono">
                                        {{ $role->guard_name }}
                                    </span>
                                </div>
                            </td>

                            {{-- Permissions (Logic Truncate) --}}
                            <td class="align-top pt-4 pb-4">
                                @php
                                    $allPermissions = $role->permissions;
                                    $totalCount = $allPermissions->count();
                                    $limit = 5; // Jumlah maksimal badge yang tampil
                                    $showPermissions = $allPermissions->take($limit);
                                    $remaining = $totalCount - $limit;
                                @endphp

                                <div class="flex flex-col gap-2">
                                    {{-- Total Count Label --}}
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                        Total: {{ $totalCount }} Hak Akses
                                    </span>

                                    {{-- Badge List --}}
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($showPermissions as $perm)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600">
                                                {{ $perm->name }}
                                            </span>
                                        @endforeach

                                        {{-- Badge Sisa (+) --}}
                                        @if($remaining > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800" title="Dan {{ $remaining }} permission lainnya...">
                                                +{{ $remaining }} Lainnya
                                            </span>
                                        @endif

                                        @if($totalCount === 0)
                                            <span class="text-xs text-slate-400 italic">Tidak ada permission khusus.</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Jumlah User --}}
                            <td class="text-center align-top pt-4">
                                <span class="badge bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $role->users_count ?? $role->users()->count() }} Users
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right align-top pt-4 sticky right-0 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors z-10 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    {{-- Edit --}}
                                    @can('manage-roles')
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn-action btn-action-edit" title="Edit Role & Permission">
                                            <i class="material-icons">edit_note</i>
                                        </a>
                                    @endcan

                                    {{-- Delete (Proteksi Admin/Superadmin) --}}
                                    @if(!in_array($role->name, ['admin', 'superadmin']))
                                        @can('manage-roles')
                                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn-action btn-action-delete" 
                                                        title="Hapus Role"
                                                        onclick="handleAction(this, 'Hapus Role?', 'Pastikan tidak ada user yang menggunakan role ini. Tindakan ini permanen.', 'danger')">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        {{-- Disabled Button untuk Admin/Superadmin --}}
                                        <button type="button" class="btn-action opacity-50 cursor-not-allowed bg-slate-100 border-slate-200 text-slate-400" title="Role Sistem (Tidak bisa dihapus)">
                                            <i class="material-icons">lock</i>
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <i class="material-icons text-4xl text-slate-300 mb-2">lock_person</i>
                                    <h3 class="text-slate-800 dark:text-white font-medium text-lg">Belum ada Role</h3>
                                    <p class="text-slate-500 text-sm mt-1">Silakan tambahkan role baru untuk mengatur akses user.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $roles->links() }}
        </div>
    </div>

    {{-- Script Handle Action --}}
    @push('scripts')
    <script>
        function handleAction(button, title, text, type) {
            event.preventDefault();
            const form = button.closest('form');
            
            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: title,
                    text: text,
                    icon: type === 'danger' ? 'error' : type,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: type
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if(confirm(text)) form.submit();
            }
        }
    </script>
    @endpush

@endsection