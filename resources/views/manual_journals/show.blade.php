@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-0">Detail Jurnal Manual</h2>
                        <span class="text-muted">{{ $manualJournal->journal_number }}</span>
                    </div>
                    <a href="{{ route('manual-journals.index') }}" class="btn btn-outline-dark">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    {{-- Bagian Header Jurnal --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">Tanggal Jurnal</label>
                            <p class="fs-5 fw-semibold">{{ $manualJournal->entry_date->format('d M Y') }}</p>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-muted">Deskripsi/Memo Jurnal</label>
                            <p class="fs-5 fw-semibold">{{ $manualJournal->description }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Dibuat Oleh</label>
                            <p class="fs-5 fw-semibold">{{ $manualJournal->user->name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <hr>
                    
                    {{-- Bagian Entri Jurnal --}}
                    <h5 class="fw-semibold">Entri Jurnal</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped" id="journal-entries-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">No. Akun</th>
                                    <th style="width: 25%;">Nama Akun</th>
                                    <th style="width: 30%;">Deskripsi Baris</th>
                                    <th style="width: 15%;" class="text-end">Debit</th>
                                    <th style="width: 15%;" class="text-end">Kredit</th>
                                </tr>
                            </thead>
                            <tbody id="journal-entries-body">
                                @foreach ($manualJournal->entries as $entry)
                                <tr class="journal-entry-row">
                                    <td>{{ $entry->account->account_number ?? 'N/A' }}</td>
                                    <td>{{ $entry->account->account_name ?? 'N/A' }}</td>
                                    <td>{{ $entry->description ?? '-' }}</td>
                                    <td class="text-end font-monospace">{{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end font-monospace">{{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th class="text-end font-monospace fs-5">Rp {{ number_format($manualJournal->total_debit, 0, ',', '.') }}</th>
                                    <th class="text-end font-monospace fs-5">Rp {{ number_format($manualJournal->total_credit, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection