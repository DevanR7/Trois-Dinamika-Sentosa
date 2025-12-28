@extends('admin.layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-subtitle">Kelola akun staf, hak akses role, dan persetujuan pengguna.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">person_add</i> Tambah User
            </a>
        </div>
    </div>

    {{-- FILTER & TABS --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                {{-- Status Tabs --}}
                <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg self-start">
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-all {{ !request('status') ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'deleted']) }}" 
                       class="px-4 py-2 text-sm font-medium rounded-md transition-all {{ request('status') == 'deleted' ? 'bg-white text-rose-600 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                        Sampah (Deleted)
                    </a>
                </div>

                {{-- Search Bar --}}
                <form action="{{ route('admin.users.index') }}" method="GET" class="w-full md:w-72">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <div class="input-group">
                        <span class="input-group-text bg-white dark:bg-slate-800 border-r-0">
                            <i class="material-icons text-slate-400 text-sm">search</i>
                        </span>
                        <input type="text" name="search" class="form-input border-l-0 pl-0" 
                               placeholder="Cari nama, email, atau username..." 
                               value="{{ request('search') }}">
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16">Avatar</th>
                        <th>Nama & Username</th>
                        <th>Role & Status</th>
                        <th>Kontak (Email/HP)</th>
                        <th>Kode Sales / NIK</th>
                        <th class="text-center w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="w-10 h-10 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-sm border-2 border-white dark:border-slate-600 shadow-sm">
                                    {{ substr($user->full_name, 0, 1) }}
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $user->full_name }}
                                </div>
                                <div class="text-xs text-slate-500 font-mono">
                                    {{ '@' . $user->username }}
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col gap-1 items-start">
                                    {{-- Role Badge --}}
                                    @foreach($user->getRoleNames() as $role)
                                        <span class="badge badge-primary uppercase text-[10px] tracking-wider">
                                            {{ $role }}
                                        </span>
                                    @endforeach

                                    {{-- Approval Status --}}
                                    @if(!$user->is_approved)
                                        <span class="badge badge-warning text-[10px]">Menunggu Approval</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-sm text-slate-600 dark:text-slate-300">
                                    {{ $user->email }}
                                </div>
                                @if($user->phone_number)
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">phone</i> {{ $user->phone_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm text-slate-500">
                                @if($user->sales_code)
                                    <div class="font-mono text-indigo-600 font-bold">{{ $user->sales_code }}</div>
                                @endif
                                @if($user->nik)
                                    <div class="text-xs">{{ $user->nik }}</div>
                                @endif
                                @if(!$user->sales_code && !$user->nik)
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    @if(request('status') === 'deleted')
                                        {{-- Restore Button --}}
                                        <form action="{{ route('admin.users.restore', $user->user_id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors shadow-sm" title="Pulihkan">
                                                <i class="material-icons text-[18px] leading-none">restore</i>
                                            </button>
                                        </form>
                                    @else
                                        {{-- Approve Button (Jika belum approved) --}}
                                        @if(!$user->is_approved)
                                            <form action="{{ route('admin.users.approve', $user->user_id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors shadow-sm" title="Setujui Akun">
                                                    <i class="material-icons text-[18px] leading-none">check_circle</i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Edit Button --}}
                                        <a href="{{ route('admin.users.edit', $user->user_id) }}" 
                                           class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm"
                                           title="Edit">
                                            <i class="material-icons text-[18px] leading-none">edit</i>
                                        </a>

                                        {{-- Delete Button (Proteksi Diri Sendiri) --}}
                                        @if(Auth::id() !== $user->user_id)
                                            <button type="button" onclick="confirmDelete('{{ $user->user_id }}', '{{ $user->full_name }}')" 
                                                    class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm"
                                                    title="Hapus">
                                                <i class="material-icons text-[18px] leading-none">delete</i>
                                            </button>
                                            
                                            <form id="delete-form-{{ $user->user_id }}" 
                                                  action="{{ route('admin.users.destroy', $user->user_id) }}" 
                                                  method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">person_off</i>
                                    <span>Tidak ada data pengguna ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Pengguna?',
            text: "User '" + name + "' akan dipindahkan ke sampah (soft delete).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush