@extends('admin.layouts.app')

@section('title', 'Daftar Klien')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Klien</h1>
            <p class="page-subtitle">Daftar pelanggan dan mitra bisnis</p>
        </div>
        <div>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                <i class="material-icons text-[18px]">person_add</i>
                Klien Baru
            </a>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                {{-- Tabs Status --}}
                <div class="flex bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1">
                    <a href="{{ route('admin.clients.index') }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') !== 'deleted' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.clients.index', ['status' => 'deleted']) }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') === 'deleted' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Arsip ({{ \App\Models\Client::onlyTrashed()->count() }})
                    </a>
                </div>

                {{-- Search --}}
                <form action="{{ route('admin.clients.index') }}" method="GET" class="w-full md:w-auto">
                    @if(request('status') === 'deleted')
                        <input type="hidden" name="status" value="deleted">
                    @endif
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px]">search</i>
                        </div>
                        <input type="text" name="search" class="form-input pl-10 w-full md:w-64" 
                               placeholder="Cari nama, email, atau HP..." 
                               value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- CLIENT LIST (ACCORDION STYLE) --}}
    <div class="space-y-4">
        @forelse($clients as $client)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-all hover:shadow-md"
                 x-data="{ expanded: false }">
                
                {{-- ACCORDION HEADER (Always Visible) --}}
                <div class="p-4 flex flex-col md:flex-row items-center justify-between gap-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                     @click="expanded = !expanded">
                    
                    {{-- Kiri: Profil Singkat --}}
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <img src="{{ $client->avatar_url }}" alt="Avatar" class="w-12 h-12 rounded-full object-cover border border-slate-200 dark:border-slate-600 shrink-0">
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-200 text-base">
                                {{ $client->client_name }}
                            </h3>
                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                <span>{{ $client->email ?? 'No Email' }}</span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span>{{ $client->person_in_charge ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Kanan: Status & Toggle Icon --}}
                    <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                        <div class="flex gap-2">
                            @if($client->is_locked)
                                <span class="badge badge-danger"><i class="material-icons text-[12px] mr-1">lock</i> Locked</span>
                            @endif
                            @if(!$client->is_approved)
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge badge-success">Verified</span>
                            @endif
                        </div>
                        
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center transition-transform duration-300"
                             :class="expanded ? 'rotate-180' : ''">
                            <i class="material-icons text-slate-500">expand_more</i>
                        </div>
                    </div>
                </div>

                {{-- ACCORDION BODY (Details & Actions) --}}
                <div x-show="expanded" x-collapse style="display: none;">
                    <div class="border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 p-5">
                        
                        {{-- Grid Informasi Detail --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            
                            {{-- Info Kontak --}}
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Kontak</p>
                                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    <li class="flex items-center gap-2">
                                        <i class="material-icons text-[16px] text-slate-400">phone</i>
                                        {{ $client->phone_number ?? '-' }}
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="material-icons text-[16px] text-slate-400 mt-0.5">place</i>
                                        <span class="text-xs leading-relaxed">{{ $client->address ?? '-' }}</span>
                                    </li>
                                </ul>
                            </div>

                            {{-- Info Keuangan --}}
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Posisi Keuangan</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white dark:bg-slate-700 p-2 rounded border border-slate-200 dark:border-slate-600">
                                        <span class="text-xs text-slate-500">Saldo Deposit</span>
                                        <span class="text-sm font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                            Rp {{ number_format($client->balance, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white dark:bg-slate-700 p-2 rounded border border-slate-200 dark:border-slate-600">
                                        <span class="text-xs text-slate-500">Saldo Tertahan</span>
                                        <span class="text-sm font-mono font-bold text-amber-600 dark:text-amber-400">
                                            Rp {{ number_format($client->pending_balance, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Quick Stats --}}
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Statistik</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="text-center p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded">
                                        <span class="block text-xs text-indigo-500 font-bold">Invoice</span>
                                        <span class="block text-lg font-bold text-indigo-700 dark:text-indigo-300">
                                            {{ $client->salesInvoices->count() }}
                                        </span>
                                    </div>
                                    <div class="text-center p-2 bg-rose-50 dark:bg-rose-900/20 rounded">
                                        <span class="block text-xs text-rose-500 font-bold">Retur</span>
                                        <span class="block text-lg font-bold text-rose-700 dark:text-rose-300">
                                            {{ $client->salesInvoices->sum(fn($i) => $i->returns->count()) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons Toolbar --}}
                        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                            
                            {{-- 1. View Detail --}}
                            <a href="{{ route('admin.clients.show', $client->client_id) }}" class="btn btn-sm btn-secondary">
                                <i class="material-icons text-[16px]">visibility</i> Detail
                            </a>

                            {{-- 2. Edit --}}
                            <a href="{{ route('admin.clients.edit', $client->client_id) }}" class="btn btn-sm btn-secondary">
                                <i class="material-icons text-[16px] text-indigo-600">edit</i> Edit
                            </a>

                            {{-- 3. WhatsApp API --}}
                            @if($client->phone_number)
                                @php
                                    // Bersihkan nomor (hapus spasi, -, +) lalu ganti 0 di depan dengan 62
                                    $waNumber = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $client->phone_number));
                                @endphp
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo {{ urlencode($client->client_name) }}," target="_blank" class="btn btn-sm btn-secondary hover:text-emerald-600 hover:border-emerald-300">
                                    <i class="material-icons text-[16px] text-emerald-500">chat</i> WhatsApp
                                </a>
                            @endif

                            <div class="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1"></div>

                            {{-- 4. Approve (Jika belum) --}}
                            @if(!$client->is_approved)
                                <form action="{{ route('admin.clients.approve', $client->client_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success text-white">
                                        <i class="material-icons text-[16px]">check_circle</i> Setujui
                                    </button>
                                </form>
                            @endif

                            {{-- 5. Lock/Unlock --}}
                            @if($client->is_locked)
                                <form action="{{ route('admin.clients.unlock', $client->client_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-secondary hover:text-amber-600">
                                        <i class="material-icons text-[16px]">lock_open</i> Buka Kunci
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.clients.lock', $client->client_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="button" 
                                            onclick="confirmDialog({title: 'Kunci Akun?', text: 'Klien tidak akan bisa login atau bertransaksi.', icon: 'warning', confirmText: 'Kunci', confirmColor: 'warning'}).then(res => { if(res.isConfirmed) this.closest('form').submit() })"
                                            class="btn btn-sm btn-secondary hover:text-rose-600">
                                        <i class="material-icons text-[16px]">lock</i> Kunci
                                    </button>
                                </form>
                            @endif

                            {{-- 6. Delete/Restore --}}
                            @if(request('status') === 'deleted')
                                <form action="{{ route('admin.clients.restore', $client->client_id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-secondary hover:text-emerald-600">
                                        <i class="material-icons text-[16px]">restore</i> Pulihkan
                                    </button>
                                </form>
                            @else
                                <button type="button" 
                                        onclick="confirmDialog({
                                            title: 'Arsipkan Klien?',
                                            text: 'Data klien akan dipindahkan ke arsip sampah.',
                                            icon: 'warning',
                                            confirmText: 'Ya, Arsipkan',
                                            confirmColor: 'danger'
                                        }).then((res) => { if(res.isConfirmed) document.getElementById('del-{{ $client->client_id }}').submit() })"
                                        class="btn btn-sm btn-secondary hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 ml-auto">
                                    <i class="material-icons text-[16px]">delete</i> Arsip
                                </button>
                                <form id="del-{{ $client->client_id }}" action="{{ route('admin.clients.destroy', $client->client_id) }}" method="POST" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center text-slate-400">
                <i class="material-icons text-4xl mb-3">person_off</i>
                <p>Tidak ada data klien ditemukan.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $clients->links('vendor.pagination.admin') }}
    </div>
@endsection