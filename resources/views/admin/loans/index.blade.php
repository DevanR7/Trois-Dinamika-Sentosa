@extends('admin.layouts.app')

@section('title', 'Daftar Pinjaman')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 animate-enter">
        <div>
            <h1 class="page-title">Pinjaman & Hutang Bank</h1>
            <p class="page-subtitle">Kelola daftar pinjaman perusahaan, monitoring sisa pokok, dan riwayat pembayaran.</p>
        </div>
        
        @can('manage-loans')
        <a href="{{ route('admin.loans.create') }}" class="btn btn-primary">
            <i class="material-icons text-sm">add</i>
            Buat Pinjaman Baru
        </a>
        @endcan
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-6 animate-enter" style="animation-delay: 0.1s">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.loans.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cari Pemberi Pinjaman</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" placeholder="Contoh: Bank BCA, Leasing...">
                        <i class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</i>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                    <select name="status" class="tom-select">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active (Belum Lunas)</option>
                        <option value="paid_off" {{ request('status') == 'paid_off' ? 'selected' : '' }}>Paid Off (Lunas)</option>
                    </select>
                </div>

                {{-- Button --}}
                <div class="flex items-end">
                    <button type="submit" class="btn btn-secondary w-full">
                        <i class="material-icons text-sm">filter_list</i>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table List --}}
    <div class="card animate-enter" style="animation-delay: 0.2s">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Pemberi Pinjaman</th>
                        <th>Tanggal Terima</th>
                        <th class="text-right">Pokok Pinjaman</th>
                        <th class="text-right">Sisa Pokok</th>
                        <th class="text-center">Progress</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        @php
                            $percentage = $loan->principal_amount > 0 
                                ? round((($loan->principal_amount - $loan->remaining_balance) / $loan->principal_amount) * 100) 
                                : 0;
                        @endphp
                        <tr>
                            <td class="font-bold text-slate-700 dark:text-white">
                                {{ $loan->lender_name }}
                                <div class="text-xs font-normal text-slate-500 truncate max-w-[200px]">
                                    {{ $loan->description ?? '-' }}
                                </div>
                            </td>
                            <td>{{ $loan->loan_date->format('d M Y') }}</td>
                            <td class="text-right font-medium text-slate-700 dark:text-slate-300 autonumeric" data-a-sign="Rp ">
                                {{ $loan->principal_amount }}
                            </td>
                            <td class="text-right font-bold text-indigo-600 dark:text-indigo-400 autonumeric" data-a-sign="Rp ">
                                {{ $loan->remaining_balance }}
                            </td>
                            <td class="align-middle" style="min-width: 100px;">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 dark:bg-slate-700">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-500">{{ $percentage }}%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($loan->status == 'active')
                                    <span class="badge badge-primary">Active</span>
                                @else
                                    <span class="badge badge-success">Lunas</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Tombol Detail (Mata) --}}
                                    {{-- Gunakan w-8 h-8 flex center agar icon presisi di tengah --}}
                                    <a href="{{ route('admin.loans.show', $loan->loan_id) }}" 
                                       class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-slate-50 hover:text-indigo-600 flex items-center justify-center transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white"
                                       title="Detail & Pembayaran">
                                        <i class="material-icons text-[18px]">visibility</i>
                                    </a>

                                    @can('manage-loans')
                                        {{-- Tombol Edit (Pensil) --}}
                                        <a href="{{ route('admin.loans.edit', $loan->loan_id) }}" 
                                           class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-slate-50 hover:text-amber-600 flex items-center justify-center transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white"
                                           title="Edit">
                                            <i class="material-icons text-[18px]">edit</i>
                                        </a>

                                        {{-- Tombol Hapus (Tong Sampah) --}}
                                        @if($loan->payments_count == 0)
                                            <form action="{{ route('admin.loans.destroy', $loan->loan_id) }}" method="POST" onsubmit="return confirm('Hapus data pinjaman ini? Jurnal akan direverse.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" 
                                                        class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-red-50 hover:text-red-600 flex items-center justify-center transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white"
                                                        title="Hapus">
                                                    <i class="material-icons text-[18px]">delete</i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="material-icons text-4xl mb-2">account_balance</i>
                                <p>Belum ada data pinjaman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $loans->links() }}
        </div>
    </div>
@endsection