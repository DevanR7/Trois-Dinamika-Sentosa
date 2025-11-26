@extends('layouts.app') 

@section('title', 'Manajemen User')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen User</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola akses, edit profil, dan pantau status pengguna.</p>
        </div>
        
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            @if(request('status') === 'deleted')
                <a href="{{ route('users.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center gap-2">
                    <i class="material-icons text-[18px]">arrow_back</i> Kembali ke User Aktif
                </a>
            @else
                <a href="{{ route('users.index', ['status' => 'deleted']) }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm flex items-center gap-2">
                    <i class="material-icons text-[18px] text-slate-400">archive</i> Arsip
                </a>
                @can('manage-users')
                <a href="{{ route('users.create') }}" 
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">add</i> Tambah User
                </a>
                @endcan
            @endif
        </div>
    </div>
    
    {{-- USERS GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($users as $user)
        <div class="dashboard-card group p-0 overflow-hidden hover:ring-1 hover:ring-indigo-300 transition-all duration-300 flex flex-col h-full">
            
            {{-- Card Header & Avatar --}}
            <div class="p-5 flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg font-bold shadow-inner group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-slate-800 leading-tight group-hover:text-indigo-700 transition-colors">{{ $user->full_name }}</h4>
                        <span class="text-xs text-slate-400 font-mono mt-0.5 block">@ {{ $user->username }}</span>
                    </div>
                </div>
                
                {{-- Role Badge --}}
                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border bg-indigo-50 text-indigo-600 border-indigo-100">
                    {{ $user->getRoleNames()->first() ?? 'User' }}
                </span>
            </div>

            {{-- Card Body (Info) --}}
            <div class="px-5 pb-4 flex-grow space-y-3">
                <div class="flex items-center gap-3 text-sm text-slate-600">
                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                        <i class="material-icons text-[16px]">email</i>
                    </div>
                    <span class="truncate">{{ $user->email }}</span>
                </div>
                
                @if($user->phone_number)
                <div class="flex items-center gap-3 text-sm text-slate-600">
                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                        <i class="material-icons text-[16px]">call</i>
                    </div>
                    <span>{{ $user->phone_number }}</span>
                </div>
                @endif
                
                <div class="pt-2 border-t border-slate-50 mt-2 flex items-center justify-between">
                    <span class="text-xs text-slate-400">Status Akun:</span>
                    @if($user->trashed())
                        <span class="status-badge status-rejected"><i class="material-icons text-[12px]">archive</i> Diarsipkan</span>
                    @elseif($user->is_approved)
                        <span class="status-badge status-completed"><i class="material-icons text-[12px]">check_circle</i> Aktif</span>
                    @else
                        <span class="status-badge status-pending"><i class="material-icons text-[12px]">schedule</i> Pending</span>
                    @endif
                </div>
            </div>

            {{-- Card Footer (Actions) --}}
            <div class="bg-slate-50/50 px-5 py-3 border-t border-slate-100 flex justify-end items-center gap-2">
                @if($user->trashed())
                    @can('manage-users')
                    <form action="{{ route('users.restore', $user->user_id) }}" method="POST" class="form-confirm" 
                          data-title="Pulihkan User?" data-text="User <b>{{ $user->full_name }}</b> akan aktif kembali." 
                          data-btn-color="#10b981" data-btn-text="Ya, Pulihkan">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs font-bold text-emerald-600 bg-white border border-emerald-200 hover:bg-emerald-50 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1 shadow-sm">
                            <i class="material-icons text-[14px]">restore</i> Pulihkan
                        </button>
                    </form>
                    @endcan
                @else
                    @can('manage-users')
                        {{-- Approve Button --}}
                        @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                        <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-confirm"
                              data-title="Setujui User?" data-text="Akun <b>{{ $user->full_name }}</b> akan diaktifkan."
                              data-btn-color="#10b981" data-btn-text="Ya, Setujui">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 border border-transparent px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1">
                                <i class="material-icons text-[14px]">check</i> Setujui
                            </button>
                        </form>
                        @endif
                        
                        {{-- Edit Button --}}
                        <a href="{{ route('users.edit', $user->user_id) }}" class="text-xs font-bold text-slate-600 bg-white border border-slate-300 hover:border-indigo-300 hover:text-indigo-600 px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1">
                            <i class="material-icons text-[14px]">edit</i> Edit
                        </a>
                        
                        {{-- Delete Button (Merah/Danger) --}}
                        @if(Auth::id() !== $user->user_id)
                        <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-confirm"
                              data-title="Arsipkan User?" data-text="User <b>{{ $user->full_name }}</b> akan diarsipkan dan tidak bisa login."
                              data-btn-color="#ef4444" data-btn-text="Ya, Arsipkan">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-bold text-red-600 bg-white border border-red-200 hover:bg-red-50 hover:border-red-300 px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1" title="Arsipkan">
                                <i class="material-icons text-[14px]">delete</i>
                            </button>
                        </form>
                        @endif
                    @endcan
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <i class="material-icons text-slate-300 text-4xl">search_off</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Tidak ada data ditemukan</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-xs mx-auto">Coba sesuaikan filter atau tambahkan user baru jika data masih kosong.</p>
                @if(request('status') === 'deleted')
                    <a href="{{ route('users.index') }}" class="mt-4 text-indigo-600 font-bold text-sm hover:underline">Kembali ke User Aktif</a>
                @endif
            </div>
        </div>
        @endforelse
    </div>
    
    {{-- Pagination --}}
    <div class="mt-8">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Toast Notification (Ringan) - Menggunakan Global Function dari app.js
    @if(session('success')) 
        window.showToast("{{ session('success') }}", 'success'); 
    @endif
    @if(session('error')) 
        window.showToast("{{ session('error') }}", 'error'); 
    @endif

    // 2. SweetAlert Confirmation (Berat/Dangerous Action)
    const confirmForms = document.querySelectorAll('.form-confirm');
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Ambil data dari atribut form
            const title = this.dataset.title;
            const text = this.dataset.text;
            const btnColor = this.dataset.btnColor;
            const btnText = this.dataset.btnText;

            Swal.fire({
                title: title,
                html: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: btnColor,
                cancelButtonColor: '#94a3b8', // Slate-400
                confirmButtonText: btnText,
                cancelButtonText: 'Batal',
                reverseButtons: true, // Tombol confirm di kanan
                customClass: {
                    popup: 'rounded-xl border border-slate-100 shadow-2xl',
                    title: 'text-slate-800 font-bold',
                    htmlContainer: 'text-slate-600',
                    confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-lg',
                    cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
});
</script>
@endpush