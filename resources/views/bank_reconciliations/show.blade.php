@extends('layouts.app')

@push('styles')
<style>
    .recon-summary {
        font-size: 1.1rem;
    }
    .recon-table-container {
        max-height: 400px;
        overflow-y: auto;
    }
    .table-sm th, .table-sm td {
        padding: 0.4rem;
        font-size: 0.9rem;
    }
    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .total-row td {
        border-top: 2px solid #000 !important;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    <form action="{{ route('bank-reconciliations.update', $bankReconciliation) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Rekonsiliasi Bank: {{ $bankReconciliation->account->account_name }}</h2>
                <span class="text-muted">Untuk Tanggal Laporan: {{ $bankReconciliation->statement_date->format('d F Y') }}</span>
            </div>
            <div>
                <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-lg"></i> Tutup
                </a>
                @if($bankReconciliation->status == 'draft')
                <button type="submit" name="action" value="save_draft" class="btn btn-dark">
                    <i class="bi bi-save-fill"></i> Simpan Draft
                </button>
                <button type="submit" name="action" value="reconcile" class="btn btn-success" {{ round($difference, 2) != 0 ? 'disabled' : '' }}>
                    <i class="bi bi-check-circle-fill"></i> Selesaikan Rekonsiliasi
                </button>
                @endif
            </div>
        </div>

        @if ($errors->any() || session('error') || session('success'))
        <div class="alert {{ session('success') ? 'alert-success' : 'alert-danger' }} alert-dismissible fade show">
            @if(session('success'))
                {{ session('success') }}
            @else
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    @if (session('error'))<li>{{ session('error') }}</li>@endif
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        
        {{-- Ringkasan Kalkulasi --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body recon-summary">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted">Saldo Akhir Rekening Koran (Bank)</div>
                        <div class="fw-bold fs-4">Rp {{ number_format($statementBalance, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Saldo Akhir di Jurnal Umum (Sistem)</div>
                        <div class="fw-bold fs-4">Rp {{ number_format($closingBalance, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">Selisih (Harus 0)</div>
                        @if(round($difference, 2) == 0)
                            <div class="fw-bold fs-4 text-success">Rp 0,00</div>
                        @else
                            <div class="fw-bold fs-4 text-danger">Rp {{ number_format($difference, 2, ',', '.') }}</div>
                            <small class="text-danger">Selisih ini disebabkan oleh item yang belum dicentang di bawah.</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Dua Kolom Transaksi --}}
        <div class="row g-4">
            {{-- Kolom Kiri: Setoran/Pemasukan (DEBIT JURNAL UMUM) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Setoran & Pemasukan (Debit)</h5>
                    </div>
                    <div class="card-body recon-table-container">
                        <table class="table table-sm table-hover">
                            <thead class="table-light sticky-header">
                                <tr>
                                    <th><i class="bi bi-check-square-fill"></i></th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- 1. Yang sudah dicentang (Cleared) --}}
                                @foreach ($cleared_deposits as $entry)
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" id="entry-{{$entry->ledger_id}}" checked>
                                    </td>
                                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-end">Rp {{ number_format($entry->debit, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                
                                {{-- 2. Yang belum dicentang (Unreconciled) --}}
                                @foreach ($unreconciled_deposits as $entry)
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" id="entry-{{$entry->ledger_id}}">
                                    </td>
                                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-end">Rp {{ number_format($entry->debit, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($cleared_deposits->isEmpty() && $unreconciled_deposits->isEmpty())
                            <p class="text-center text-muted p-3">Tidak ada data setoran/pemasukan pada periode ini.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Cek/Pembayaran (KREDIT JURNAL UMUM) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Cek & Pembayaran (Kredit)</h5>
                    </div>
                    <div class="card-body recon-table-container">
                        <table class="table table-sm table-hover">
                            <thead class="table-light sticky-header">
                                <tr>
                                    <th><i class="bi bi-check-square-fill"></i></th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- 1. Yang sudah dicentang (Cleared) --}}
                                @foreach ($cleared_payments as $entry)
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" id="entry-{{$entry->ledger_id}}" checked>
                                    </td>
                                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-end">(Rp {{ number_format($entry->credit, 0, ',', '.') }})</td>
                                </tr>
                                @endforeach
                                
                                {{-- 2. Yang belum dicentang (Unreconciled) --}}
                                @foreach ($unreconciled_payments as $entry)
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="cleared_entries[]" value="{{ $entry->ledger_id }}" id="entry-{{$entry->ledger_id}}">
                                    </td>
                                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $entry->description }}</td>
                                    <td class="text-end">(Rp {{ number_format($entry->credit, 0, ',', '.') }})</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($cleared_payments->isEmpty() && $unreconciled_payments->isEmpty())
                            <p class="text-center text-muted p-3">Tidak ada data pembayaran pada periode ini.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
    </form> {{-- Akhir Form --}}
    
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="fw-semibold">Butuh Penyesuaian?</h5>
            <p class="text-muted">Jika ada transaksi di rekening koran (seperti biaya admin bank atau bunga) yang belum ada di daftar atas, Anda harus mencatatnya terlebih dahulu melalui Jurnal Umum Manual agar muncul di sini.</p>
            <a href="{{ route('manual-journals.create') }}" class="btn btn-outline-dark" target="_blank">
                <i class="bi bi-plus-lg"></i> Buat Jurnal Manual (Koreksi)
            </a>
        </div>
    </div>

</div>
@endsection