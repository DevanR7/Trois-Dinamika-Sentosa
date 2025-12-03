@extends('admin.layouts.app')

@section('title', 'Manajemen Role')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Role</h1>
            <p class="text-slate-500 text-sm mt-1">Atur peran pengguna dan hak akses sistem.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('admin.roles.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                <i class="material-icons text-[20px]">add</i> Tambah Role
            </a>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-1/4">Nama Role</th>
                        <th>Hak Akses (Permissions)</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <i class="material-icons text-slate-400 text-[18px]">badge</i>
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
                                        {{ Str::title($role->name) }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($role->permissions->take(8) as $permission)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wide">
                                            {{ str_replace(['-', '_'], ' ', $permission->name) }}
                                        </span>
                                    @endforeach
                                    
                                    @if($role->permissions->count() > 8)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-white text-indigo-600 border border-indigo-100">
                                            +{{ $role->permissions->count() - 8 }} lainnya
                                        </span>
                                    @endif
                                    
                                    @if($role->permissions->isEmpty())
                                        <span class="text-xs text-slate-400 italic flex items-center gap-1">
                                            <i class="material-icons text-[14px]">block</i> Tidak ada akses
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="pr-6 py-4 align-top text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-amber-600 hover:border-amber-200 transition-colors shadow-sm" 
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    {{-- Global Delete Handler --}}
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ Str::title($role->name) }}"
                                                data-title="Hapus Role?"
                                                data-text="Pastikan tidak ada user yang menggunakan role <b>{{ Str::title($role->name) }}</b>."
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 transition-colors shadow-sm" 
                                                title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">gpp_bad</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data role</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan role baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush