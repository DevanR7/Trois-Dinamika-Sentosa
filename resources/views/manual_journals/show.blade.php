@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Detail Jurnal</h4>
                        <span class="badge bg-dark">{{ $manualJournal->journal_number }}</span>
                    </div>
                    <a href="{{ route('manual-journals.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
                <div class="card-body">
                    {{-- Info Utama --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted" style="width: 130px;">Tanggal</td>
                                    <td class="fw-bold">: {{ $manualJournal->entry_date->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Dibuat Oleh</td>
                                    <td class="fw-bold">: {{ $manualJournal->user->name ?? 'Sistem' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-light border">
                                <small class="text-muted d-block">Deskripsi / Memo:</small>
                                <span class="fw-semibold text-dark">{{ $manualJournal->description }}</span>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Rincian Akun</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Kode Akun</th>
                                    <th>Nama Akun</th>
                                    <th>Deskripsi Baris</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($manualJournal->entries as $entry)
                                <tr>
                                    <td>{{ $entry->account->account_number ?? '-' }}</td>
                                    <td>{{ $entry->account->account_name ?? '-' }}</td>
                                    <td class="text-muted small">{{ $entry->description ?? '-' }}</td>
                                    <td class="text-end font-monospace text-primary">
                                        {{ $entry->debit > 0 ? 'Rp '.number_format($entry->debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end font-monospace text-danger">
                                        {{ $entry->credit > 0 ? 'Rp '.number_format($entry->credit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTAL</td>
                                    <td class="text-end font-monospace fs-6 text-primary">
                                        Rp {{ number_format($manualJournal->total_debit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace fs-6 text-danger">
                                        Rp {{ number_format($manualJournal->total_credit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('manual-journals.edit', $manualJournal) }}" class="btn btn-warning">
                            <i class="bi bi-pencil-square"></i> Edit Jurnal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Cek Flash Message Success (Misal setelah update lalu redirect ke show)
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif
</script>
@endpush