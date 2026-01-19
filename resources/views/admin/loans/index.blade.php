@extends('admin.layouts.app')

@section('title', 'Daftar Pinjaman')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Pinjaman</h1>
            <p class="page-subtitle">Daftar hutang perusahaan (Liabilitas) dan status pelunasan</p>
        </div>
        <div>
            <a href="{{ route('admin.loans.create') }}" class="btn btn-primary">
                <i class="material-icons text-[18px]">add</i>
                Catat Pinjaman Baru
            </a>
        </div>
    </div>

    {{-- Filter & Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        {{-- Stat Card: Total Hutang Aktif --}}
        <div class="card p-4 flex items-center gap-4 border-l-4 border-indigo-500">
            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i class="material-icons text-2xl">account_balance</i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-bold uppercase">Sisa Pokok Hutang</p>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                    Rp {{ number_format(\App\Models\Loan::where('status', 'active')->sum('remaining_balance'), 0, ',', '.') }}
                </h3>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="md:col-span-3 card">
            <div class="card-body py-4">
                <form action="{{ route('admin.loans.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                    {{-- Status Filter --}}
                    <div class="w-full md:w-48">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Belum Lunas</option>
                            <option value="paid_off" {{ request('status') == 'paid_off' ? 'selected' : '' }}>Lunas</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px]">search</i>
                        </div>
                        <input type="text" name="search" class="form-input pl-10" 
                               placeholder="Cari nama pemberi pinjaman..." 
                               value="{{ request('search') }}">
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="material-icons text-[18px]">filter_list</i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pemberi Pinjaman</th>
                        <th class="text-right">Pokok Pinjaman</th>
                        <th class="text-right">Sisa Hutang</th>
                        <th class="w-32">Progress</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td>
                            <span class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $loan->loan_date->format('d M Y') }}
                            </span>
                        </td>
                        <td>
                            <div class="font-bold text-slate-700 dark:text-slate-200">
                                {{ $loan->lender_name }}
                            </div>
                            <div class="text-xs text-slate-500 truncate max-w-[150px]">
                                {{ $loan->description ?? '-' }}
                            </div>
                        </td>
                        <td class="text-right font-mono">
                            Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono font-bold text-slate-700 dark:text-white">
                            Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                        </td>
                        <td>
                            {{-- Progress Bar Pelunasan --}}
                            @php
                                $percentPaid = 0;
                                if($loan->principal_amount > 0) {
                                    $paid = $loan->principal_amount - $loan->remaining_balance;
                                    $percentPaid = ($paid / $loan->principal_amount) * 100;
                                }
                            @endphp
                            <div class="w-full bg-slate-200 rounded-full h-1.5 dark:bg-slate-700 mt-1">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $percentPaid }}%"></div>
                            </div>
                            <div class="text-[10px] text-slate-400 text-right mt-0.5">
                                {{ number_format($percentPaid, 0) }}% Lunas
                            </div>
                        </td>
                        <td>
                            @if($loan->status == 'active')
                                <span class="badge badge-warning">Belum Lunas</span>
                            @else
                                <span class="badge badge-success">Lunas</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="flex items-center justify-end gap-2">
                                {{-- View --}}
                                <a href="{{ route('admin.loans.show', $loan->loan_id) }}" class="btn-action btn-action-view" title="Lihat Detail & Bayar">
                                    <i class="material-icons">visibility</i>
                                </a>

                                {{-- Edit & Delete (Hanya jika belum ada pembayaran) --}}
                                @if($loan->payments_count == 0)
                                    <a href="{{ route('admin.loans.edit', $loan->loan_id) }}" class="btn-action btn-action-edit" title="Edit">
                                        <i class="material-icons">edit</i>
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Hapus"
                                            onclick="confirmDialog({
                                                title: 'Hapus Pinjaman?',
                                                text: 'Data pinjaman dan jurnal terkait akan dihapus permanen.',
                                                icon: 'warning',
                                                confirmText: 'Ya, Hapus',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('delete-form-{{ $loan->loan_id }}').submit();
                                            })">
                                        <i class="material-icons">delete_outline</i>
                                    </button>
                                    <form id="delete-form-{{ $loan->loan_id }}" action="{{ route('admin.loans.destroy', $loan->loan_id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400">
                            <i class="material-icons text-4xl mb-2">money_off</i>
                            <p>Tidak ada data pinjaman.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $loans->links('vendor.pagination.admin') }}
        </div>
    </div>
@endsection