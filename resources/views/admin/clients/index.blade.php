@extends('admin.layouts.app')

@section('title', 'Data Klien')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Tools --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Data Klien</h2>
                <p class="page-subtitle">Kelola data pelanggan, deposit, dan status akun.</p>
            </div>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i>
                Tambah Klien
            </a>
        </div>

        {{-- Filter & Search --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.clients.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="form-label text-xs">Cari Nama / Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-lg">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="Cari nama, email, atau PIC...">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="form-label text-xs">Status Data</label>
                    <select name="status" class="tom-select w-full">
                        <option value="active" {{ request('status') != 'deleted' ? 'selected' : '' }}>Aktif</option>
                        <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>Diarsipkan (Terhapus)</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-lg">filter_list</i>
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('admin.clients.index') }}" class="btn btn-danger-solid px-3" title="Reset Filter">
                            <i class="material-icons">close</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Client List (Accordion Style) --}}
        <div class="flex flex-col gap-3">
            @forelse($clients as $client)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all duration-200 hover:shadow-md hover:border-indigo-200 dark:hover:border-slate-600" 
                     x-data="{ expanded: false }">
                    
                    {{-- ACCORDION HEADER (Visible Always) --}}
                    <div @click="expanded = !expanded" class="p-4 flex items-center justify-between cursor-pointer group">
                        <div class="flex items-center gap-4">
                            {{-- Avatar Initials --}}
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold text-sm group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                {{ substr($client->client_name, 0, 1) }}
                            </div>

                            {{-- Nama & Email --}}
                            <div>
                                <h3 class="font-bold text-slate-700 dark:text-white text-sm sm:text-base group-hover:text-indigo-600 transition-colors">
                                    {{ $client->client_name }}
                                </h3>
                                <p class="text-xs text-slate-500 flex items-center gap-1">
                                    {{ $client->email ?? 'Tanpa Email' }}
                                    @if(!$client->is_approved)
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 ml-1" title="Perlu Approval"></span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            {{-- Status Badge (Compact) --}}
                            <div class="hidden sm:block">
                                @if($client->trashed())
                                    <span class="badge badge-danger">Diarsipkan</span>
                                @elseif($client->is_locked)
                                    <span class="badge badge-danger"><i class="material-icons text-[12px] mr-1">lock</i> Terkunci</span>
                                @elseif(!$client->is_approved)
                                    <span class="badge badge-warning">Pending Approval</span>
                                @else
                                    <span class="badge badge-success">Aktif</span>
                                @endif
                            </div>

                            {{-- Arrow Icon --}}
                            <div class="text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">
                                <i class="material-icons text-xl">expand_more</i>
                            </div>
                        </div>
                    </div>

                    {{-- ACCORDION BODY (Details) --}}
                    <div x-show="expanded" x-collapse class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 px-4 py-6">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {{-- KOLOM 1: Informasi Keuangan --}}
                            <div class="flex flex-col gap-4 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700 pb-4 lg:pb-0 lg:pr-6">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Informasi Deposit</h4>
                                
                                {{-- Saldo Aktif --}}
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Saldo Tersedia</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-lg">
                                        Rp {{ number_format($client->balance, 0, ',', '.') }}
                                    </span>
                                </div>
                                
                                {{-- Saldo Pending --}}
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-1">
                                        Deposit Tertahan <i class="material-icons text-[14px] text-amber-500" title="Menunggu kliring/approval">help</i>
                                    </span>
                                    <span class="font-bold text-amber-500 text-sm">
                                        Rp {{ number_format($client->pending_balance, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>

                            {{-- KOLOM 2: Statistik Invoice & Kontak --}}
                            <div class="flex flex-col gap-4 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700 pb-4 lg:pb-0 lg:pr-6">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status Tagihan & Kontak</h4>
                                
                                {{-- Kalkulasi Invoice Belum Lunas (Logic View) --}}
                                @php
                                    $unpaidInvoices = $client->salesInvoices->whereIn('status', ['unpaid', 'partially_paid']);
                                    $unpaidCount = $unpaidInvoices->count();
                                    $unpaidAmount = $unpaidInvoices->sum('remaining_balance');
                                @endphp

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Tagihan Belum Lunas</span>
                                    <div class="text-right">
                                        <span class="font-bold text-rose-600">{{ $unpaidCount }} Invoice</span>
                                        <div class="text-[10px] text-slate-500">Total: Rp {{ number_format($unpaidAmount, 0, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300 mt-1">
                                    <i class="material-icons text-slate-400 text-sm mt-0.5">person</i>
                                    <div>
                                        <span class="block font-medium">PIC: {{ $client->person_in_charge ?? '-' }}</span>
                                        <span class="block text-xs text-slate-500">{{ $client->phone_number ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM 3: Aksi & Status --}}
                            <div class="flex flex-col gap-4 justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Aksi & Kontrol</h4>
                                    
                                    {{-- Group Button --}}
                                    <div class="flex flex-wrap gap-2">
                                        @if($client->trashed())
                                            {{-- Restore --}}
                                            <form action="{{ route('admin.clients.restore', $client->client_id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success w-full justify-center">
                                                    <i class="material-icons text-[16px]">restore</i> Pulihkan Akun
                                                </button>
                                            </form>
                                        @else
                                            {{-- Detail --}}
                                            <a href="{{ route('admin.clients.show', $client->client_id) }}" class="btn btn-sm btn-primary">
                                                <i class="material-icons text-[16px]">visibility</i> Detail
                                            </a>

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.clients.edit', $client->client_id) }}" class="btn btn-sm btn-secondary">
                                                <i class="material-icons text-[16px]">edit</i> Edit
                                            </a>

                                            {{-- Lock / Unlock --}}
                                            @if($client->is_locked)
                                                <form action="{{ route('admin.clients.unlock', $client->client_id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Buka Kunci">
                                                        <i class="material-icons text-[16px]">lock_open</i> Unlock
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.clients.lock', $client->client_id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-secondary text-slate-500 hover:text-amber-600" title="Kunci Akun">
                                                        <i class="material-icons text-[16px]">lock</i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Approve --}}
                                            @if(!$client->is_approved)
                                                <form action="{{ route('admin.clients.approve', $client->client_id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                                        <i class="material-icons text-[16px]">check</i> Approve
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Delete --}}
                                            <button type="button" onclick="deleteClient({{ $client->client_id }}, '{{ $client->client_name }}')" class="btn btn-sm btn-danger ml-auto">
                                                <i class="material-icons text-[16px]">delete</i>
                                            </button>
                                            <form id="delete-form-{{ $client->client_id }}" action="{{ route('admin.clients.destroy', $client->client_id) }}" method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status Text Detail --}}
                                <div class="text-right">
                                     <span class="text-xs text-slate-400">
                                        Status: 
                                        @if($client->trashed()) <span class="text-red-500 font-bold">Terhapus</span>
                                        @elseif($client->is_locked) <span class="text-red-500 font-bold">Terkunci (Login Nonaktif)</span>
                                        @elseif(!$client->is_approved) <span class="text-amber-500 font-bold">Menunggu Persetujuan Admin</span>
                                        @else <span class="text-emerald-600 font-bold">Aktif & Terverifikasi</span>
                                        @endif
                                     </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="card p-8 flex flex-col items-center justify-center text-slate-400">
                    <i class="material-icons text-5xl mb-3">person_off</i>
                    <p class="text-base font-medium">Tidak ada data klien yang ditemukan.</p>
                    <p class="text-sm mt-1">Coba ubah kata kunci pencarian atau filter status.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($clients->hasPages())
            <div class="mt-4">
                {{ $clients->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function deleteClient(id, name) {
            confirmDialog({
                title: 'Arsipkan Klien?',
                text: `Anda akan mengarsipkan data klien <b>${name}</b>. <br>Data ini tidak akan hilang permanen dan bisa dipulihkan nanti via filter "Diarsipkan".`,
                icon: 'warning',
                confirmText: 'Ya, Arsipkan',
                confirmColor: 'danger'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush
@endsection