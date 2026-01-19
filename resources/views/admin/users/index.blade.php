@extends('admin.layouts.app')

@section('title', request('status') == 'deleted' ? 'Arsip User' : 'Manajemen User')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                {{ request('status') == 'deleted' ? 'Arsip User (Dihapus)' : 'Daftar Pengguna' }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola akses, role, dan persetujuan akun pengguna sistem.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if(request('status') == 'deleted')
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-[18px]">arrow_back</i>
                    <span>Kembali ke Daftar</span>
                </a>
            @else
                {{-- Link ke Sampah --}}
                <a href="{{ route('admin.users.index', ['status' => 'deleted']) }}" 
                   class="btn bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-rose-600 transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:text-rose-400"
                   title="Lihat User Dihapus">
                    <i class="material-icons text-[18px]">delete_outline</i>
                    <span class="hidden sm:inline">Sampah</span>
                </a>

                {{-- Tambah User --}}
                @can('manage-users')
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                        <i class="material-icons text-[18px]">person_add</i>
                        <span>User Baru</span>
                    </a>
                @endcan
            @endif
        </div>
    </div>

    {{-- 2. SEARCH FILTER --}}
    <div class="card mb-6">
        <div class="card-body p-4">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10" 
                           placeholder="Cari Nama, Username, atau Email...">
                </div>

                @if(request()->filled('search'))
                    <a href="{{ route('admin.users.index', ['status' => request('status')]) }}" class="btn btn-secondary btn-icon" title="Reset">
                        <i class="material-icons">refresh</i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- 3. DATA TABLE --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Profil Pengguna</th>
                        <th>Kontak</th>
                        <th>Role</th>
                        <th class="text-center">Status</th>
                        <th class="text-right sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/50 backdrop-blur-sm w-32 px-4">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-400 text-xs">
                                {{ $users->firstItem() + $index }}
                            </td>

                            {{-- Profil --}}
                            <td>
                                <div class="flex items-center gap-4">
                                    {{-- Avatar --}}
                                    <div class="shrink-0 relative">
                                        <img src="{{ $user->avatar_url }}" 
                                             alt="{{ $user->full_name }}" 
                                             class="w-10 h-10 rounded-full border-2 border-slate-200 dark:border-slate-600 object-cover">
                                        
                                        {{-- Indikator Online/Approval --}}
                                        @if($user->is_approved)
                                            <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-800 rounded-full" title="Approved"></div>
                                        @else
                                            <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-amber-500 border-2 border-white dark:border-slate-800 rounded-full animate-pulse" title="Menunggu Approval"></div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 text-sm group-hover:text-indigo-600 transition-colors">
                                            {{ $user->full_name }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 font-mono">
                                            @ {{ $user->username }}
                                        </span>
                                        @if($user->sales_code)
                                            <span class="text-[9px] bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-600 w-fit mt-0.5 text-slate-500">
                                                Code: {{ $user->sales_code }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Kontak --}}
                            <td>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                        <i class="material-icons text-[14px] text-slate-400">email</i>
                                        {{ $user->email }}
                                    </div>
                                    @if($user->phone_number)
                                        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                            <i class="material-icons text-[14px] text-slate-400">phone</i>
                                            {{ $user->phone_number }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Role Badge --}}
                            <td>
                                @php
                                    $roleName = $user->roles->first()->name ?? 'user';
                                    $badgeColor = match($roleName) {
                                        'superadmin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        'admin' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'manager' => 'bg-rose-100 text-rose-700 border-rose-200',
                                        'finance' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'sales' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'inventory' => 'bg-orange-100 text-orange-700 border-orange-200',
                                        default => 'bg-slate-100 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass ?? $badgeColor }} capitalize border px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-sm">
                                    {{ $roleName }}
                                </span>
                            </td>

                            {{-- Status Approval --}}
                            <td class="text-center">
                                @if($user->is_approved)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <i class="material-icons text-[12px]">check_circle</i> Aktif
                                    </span>
                                @else
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-100 animate-pulse">
                                            <i class="material-icons text-[12px]">hourglass_empty</i> Pending
                                        </span>
                                        
                                        {{-- Tombol Approve Cepat --}}
                                        @can('manage-users')
                                            <form action="{{ route('admin.users.approve', $user->user_id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" 
                                                        class="text-[10px] text-indigo-600 hover:underline font-bold"
                                                        onclick="handleAction(this, 'Setujui Akun?', 'User {{ $user->full_name }} akan dapat login ke sistem.', 'success')">
                                                    Setujui Sekarang
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right sticky right-0 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors z-10 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    @if(request('status') == 'deleted')
                                        {{-- MODE SAMPAH --}}
                                        @can('manage-users')
                                            <form action="{{ route('admin.users.restore', $user->user_id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" class="btn-action btn-action-restore" title="Pulihkan Akun"
                                                        onclick="handleAction(this, 'Pulihkan User?', 'Akun {{ $user->username }} akan aktif kembali.', 'success')">
                                                    <i class="material-icons">restore</i>
                                                </button>
                                            </form>
                                            
                                            {{-- Force Delete biasanya tidak disarankan untuk user agar jejak audit tidak hilang, tapi jika diperlukan: --}}
                                            {{-- <button ... class="btn-action-delete" ...> --}}
                                        @endcan
                                    @else
                                        {{-- MODE AKTIF --}}
                                        
                                        {{-- Edit --}}
                                        @can('manage-users')
                                            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn-action btn-action-edit" title="Edit Data">
                                                <i class="material-icons">edit</i>
                                            </a>
                                        @endcan

                                        {{-- Delete --}}
                                        @can('manage-users')
                                            @if($user->user_id !== Auth::id()) {{-- Cegah hapus diri sendiri --}}
                                                <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="inline-block m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn-action btn-action-delete" 
                                                            title="Non-aktifkan User"
                                                            onclick="handleAction(this, 'Non-aktifkan User?', 'User {{ $user->username }} tidak akan bisa login lagi.', 'warning')">
                                                        <i class="material-icons">person_off</i>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" class="btn-action opacity-50 cursor-not-allowed bg-slate-100" title="Anda sedang login">
                                                    <i class="material-icons text-slate-400">lock</i>
                                                </button>
                                            @endif
                                        @endcan
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <i class="material-icons text-4xl text-slate-300 mb-2">group_off</i>
                                    <p class="text-slate-500 text-sm">Tidak ada data pengguna ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $users->links() }}
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
                    if (result.isConfirmed) form.submit();
                });
            } else {
                if(confirm(text)) form.submit();
            }
        }
    </script>
    @endpush

@endsection