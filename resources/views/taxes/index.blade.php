@extends('layouts.app')

@section('title', 'Pengaturan Pajak')

@section('content')
<div class="max-w-5xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan Pajak</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar semua tarif pajak yang berlaku (PPN, PPh, dll).</p>
        </div>
        <div>
            <a href="{{ route('taxes.create') }}" 
               class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                <i class="material-icons text-[20px]">add</i> Tambah Tarif
            </a>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-1/2">Nama Pajak</th>
                        <th>Tarif (%)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($taxes as $tax)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="pl-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                                        <i class="material-icons text-[18px]">percent</i>
                                    </div>
                                    <span class="font-bold text-slate-700">{{ $tax->name }}</span>
                                </div>
                            </td>
                            <td class="py-4">
                                <span class="text-sm font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                    {{ number_format($tax->rate, 2, ',', '.') }}%
                                </span>
                            </td>
                            <td class="py-4 text-center">
                                @if ($tax->is_active)
                                    <span class="status-badge status-completed">
                                        <i class="material-icons text-[12px]">check_circle</i> Aktif
                                    </span>
                                @else
                                    <span class="status-badge status-rejected">
                                        <i class="material-icons text-[12px]">block</i> Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 text-center pr-6">
                                <a href="{{ route('taxes.edit', $tax->id) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 bg-white text-slate-500 rounded-lg border border-slate-200 shadow-sm hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50 transition-all" 
                                   title="Edit">
                                    <i class="material-icons text-[16px]">edit</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">percent</i>
                                    </div>
                                    <h3 class="text-lg font-medium text-slate-900">Belum ada data</h3>
                                    <p class="text-slate-500 text-sm mt-1 max-w-xs">Silakan tambahkan tarif pajak baru.</p>
                                    <a href="{{ route('taxes.create') }}" class="mt-4 text-indigo-600 font-bold text-sm hover:underline">Tambah Tarif</a>
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
        // Tampilkan Toast Success/Error (Hanya logic session, karena delete tidak ada)
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush