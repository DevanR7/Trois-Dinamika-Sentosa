@extends('layouts.app')

@section('title', 'Kelola Klien')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kelola Klien</h1>
            <p class="text-slate-500 text-sm mt-1">Manajemen data pelanggan dan mitra bisnis.</p>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            @if(request('status') === 'deleted')
                <a href="{{ route('clients.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">arrow_back</i> Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('clients.index', ['status' => 'deleted']) }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">archive</i> Lihat Arsip
                </a>
                <a href="{{ route('clients.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[20px]">person_add</i> Tambah Klien
                </a>
            @endif
        </div>
    </div>

    {{-- CARD LIST --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-[25%]">Nama Klien</th>
                        <th class="w-[20%]">Kontak</th>
                        <th class="w-[15%]">PIC</th>
                        <th class="text-center w-[10%]">Status</th>
                        <th class="text-right w-[15%]">Saldo Kredit</th>
                        <th class="text-center w-[15%] pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($clients as $client)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="pl-6 py-4">
                            <div class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $client->client_name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $client->email ?? '-' }}</div>
                        </td>
                        <td class="py-4 text-sm text-slate-600">
                            {{ $client->phone_number ?? '-' }}
                        </td>
                        <td class="py-4 text-sm text-slate-600">
                            @if($client->person_in_charge)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs font-bold border border-indigo-100">
                                        {{ substr($client->person_in_charge, 0, 1) }}
                                    </div>
                                    {{ $client->person_in_charge }}
                                </div>
                            @else
                                <span class="text-slate-400 italic">-</span>
                            @endif
                        </td>
                        <td class="py-4 text-center">
                            @if($client->trashed())
                                <span class="status-badge status-rejected"><i class="material-icons text-[12px]">archive</i> Arsip</span>
                            @elseif($client->is_locked)
                                <span class="status-badge status-draft"><i class="material-icons text-[12px]">lock</i> Terkunci</span>
                            @elseif($client->is_approved)
                                <span class="status-badge status-completed"><i class="material-icons text-[12px]">check_circle</i> Aktif</span>
                            @else
                                <span class="status-badge status-pending"><i class="material-icons text-[12px]">schedule</i> Pending</span>
                            @endif
                        </td>
                        <td class="py-4 text-right font-mono text-sm font-bold">
                            @if($client->balance > 0)
                                <span class="text-emerald-600">Rp {{ number_format($client->balance, 0, ',', '.') }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        
                        {{-- KOLOM AKSI YANG DIPERBAIKI --}}
                        <td class="pr-6 py-4 text-center whitespace-nowrap">
                            <div class="flex justify-center items-center gap-1">
                                @if($client->trashed())
                                    {{-- Mode Arsip --}}
                                    <a href="{{ route('clients.show', $client->client_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm" title="Lihat">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                    
                                    <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                                        @csrf @method('PATCH')
                                        <button type="submit" 
                                                data-title="Pulihkan Klien?"
                                                data-btn-text="Ya, Pulihkan"
                                                data-btn-color="#10b981"
                                                data-icon="question"
                                                class="w-8 h-8 flex items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition shadow-sm" title="Pulihkan">
                                            <i class="material-icons text-[16px]">restore</i>
                                        </button>
                                    </form>
                                @else
                                    {{-- Mode Aktif --}}
                                    
                                    {{-- 1. Approve (Jika belum approved) --}}
                                    @if(!$client->is_approved)
                                        <form action="{{ route('clients.approve', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                                            @csrf @method('PATCH')
                                            <button type="submit" 
                                                    data-title="Setujui Klien?"
                                                    data-btn-text="Ya, Setujui"
                                                    data-btn-color="#10b981"
                                                    data-icon="check"
                                                    class="w-8 h-8 flex items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition shadow-sm" title="Setujui">
                                                <i class="material-icons text-[16px]">check</i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- 2. Lock/Unlock --}}
                                    <form action="{{ route('clients.lock', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                                        @csrf @method('PATCH')
                                        <button type="submit" 
                                                data-title="{{ $client->is_locked ? 'Buka Kunci?' : 'Kunci Akun?' }}"
                                                data-btn-text="Ya, Lanjutkan"
                                                data-btn-color="#f59e0b"
                                                data-icon="warning"
                                                class="w-8 h-8 flex items-center justify-center rounded-md bg-amber-50 border border-amber-200 text-amber-600 hover:bg-amber-100 transition shadow-sm" 
                                                title="{{ $client->is_locked ? 'Buka Kunci' : 'Kunci' }}">
                                            <i class="material-icons text-[16px]">{{ $client->is_locked ? 'lock_open' : 'lock' }}</i>
                                        </button>
                                    </form>

                                    {{-- 3. View --}}
                                    <a href="{{ route('clients.show', $client->client_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm" title="Detail">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                    
                                    {{-- 4. Edit --}}
                                    <a href="{{ route('clients.edit', $client->client_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>

                                    {{-- 5. Archive --}}
                                    <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-title="Arsipkan Klien?"
                                                data-btn-color="#ef4444"
                                                data-btn-text="Ya, Arsipkan"
                                                class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 transition shadow-sm" title="Arsipkan">
                                            <i class="material-icons text-[16px]">archive</i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                    <i class="material-icons text-4xl">people_outline</i>
                                </div>
                                <h3 class="text-lg font-medium text-slate-900">Belum ada data</h3>
                                <p class="text-slate-500 text-sm mt-1">Silakan tambahkan data klien baru.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80">
            {{ $clients->appends(request()->query())->links() }}
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