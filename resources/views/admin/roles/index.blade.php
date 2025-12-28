@extends('admin.layouts.app')

@section('title', 'Manajemen Role')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Role</h1>
            <p class="page-subtitle">Atur hak akses pengguna dalam sistem.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_moderator</i> Tambah Role
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Nama Role</th>
                        <th>Guard</th>
                        <th>Jumlah Permission</th>
                        <th>Pengguna Terkait</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $index => $role)
                        <tr>
                            <td class="text-center text-slate-500">{{ $roles->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">
                                    {{ $role->name }}
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary font-mono text-xs">{{ $role->guard_name }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $role->permissions->count() > 0 ? 'badge-success' : 'badge-warning' }}">
                                    {{ $role->permissions->count() }} Akses
                                </span>
                            </td>
                            <td>
                                <div class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $role->users()->count() }} User
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit Akses">
                                        <i class="material-icons text-[18px] leading-none">manage_accounts</i>
                                    </a>

                                    {{-- Delete Button (Proteksi Admin) --}}
                                    @if(!in_array($role->name, ['admin', 'superadmin']))
                                        <button type="button" onclick="confirmDelete('{{ $role->id }}', '{{ $role->name }}')" 
                                                class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                                title="Hapus Role">
                                            <i class="material-icons text-[18px] leading-none">delete</i>
                                        </button>
                                        
                                        <form id="delete-form-{{ $role->id }}" 
                                              action="{{ route('admin.roles.destroy', $role->id) }}" 
                                              method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @else
                                        {{-- Spacer jika tombol delete tidak ada agar kolom tetap rapi --}}
                                        <div class="w-9 h-9"></div> 
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">lock_open</i>
                                    <span>Belum ada role yang dibuat.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Role?',
            text: "Role '" + name + "' akan dihapus permanen. Pastikan tidak ada user yang menggunakan role ini.",
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