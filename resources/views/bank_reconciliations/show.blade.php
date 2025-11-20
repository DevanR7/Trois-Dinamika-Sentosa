@extends('layouts.app')

@section('styles')
<style>
    .sticky-top-card { position: sticky; top: 20px; z-index: 1000; }
    .table-recon th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .table-recon td { font-size: 0.9rem; vertical-align: middle; }
    .amount-col { font-family: 'Courier New', Courier, monospace; font-weight: 600; }
    .bg-matched { background-color: #f0fff4 !important; } /* Hijau tipis untuk baris yang dicentang */
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    <form action="{{ route('bank-reconciliations.update', $bankReconciliation) }}" method="POST" id="recon-form">
        @csrf
        @method('PUT')

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $bankReconciliation->account->account_name }}</h4>
                <span class="badge bg-secondary">Periode: {{ $bankReconciliation->statement_date->format('d M Y') }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary">Kembali</a>
                
                @if($bankReconciliation->status == 'draft')
                    <button type="submit" name="action" value="save_draft" class="btn btn-dark">
                        <i class="bi bi-save"></i> Simpan Draft
                    </button>
                    {{-- Tombol ini didisable via JS jika belum balance --}}
                    <button type="submit" name="action" value="reconcile" id="btn-finish" class="btn btn-success" disabled>
                        <i class="bi bi-check-circle-fill me-1"></i> Selesaikan & Posting
                    </button>
                @else
                    <span class="badge bg-success fs-6 px-3 py-2">Status: Reconciled <i class="bi bi-lock-fill"></i></span>
                @endif
            </div>
        </div>

        {{-- SUMMARY CARD (STICKY) --}}
        <div class="card shadow border-0 mb-4 sticky-top-card">
            <div class="card-body py-3">
                <div class="row align-items-center text-center">
                    {{-- 1. Target --}}
                    <div class="col-md-3 border-end">
                        <small class="text-muted text-uppercase fw-bold">Saldo Rekening Koran</small>
                        <h3 class="mb-0 fw-bold text-primary">Rp <span id="display-statement">{{ number_format($bankReconciliation->statement_balance, 0, ',', '.') }}</span></h3>
                    </div>

                    {{-- 2. Kalkulasi Live --}}
                    <div class="col-md-1 text-muted"><i class="bi bi-dash-lg fs-2"></i></div>
                    <div class="col-md-4 border-end">
                        <small class="text-muted text-uppercase fw-bold">Saldo Sistem (Cleared)</small>
                        {{-- Ini akan berubah via JS --}}
                        <h3 class="mb-0 fw-bold text-dark">Rp <span id="display-cleared">0</span></h3>
                        <small class="text-muted fst-italic">(Awal + Deposit Dicentang - Pembayaran Dicentang)</small>
                    </div>
                    
                    {{-- 3. Selisih --}}
                    <div class="col-md-1 text-muted"><i class="bi bi-pause fs-2" style="transform: rotate(90deg);"></i></div>
                    <div class="col-md-3">
                        <small class="text-muted text-uppercase fw-bold">Selisih (Harus 0)</small>
                        <h3 class="mb-0 fw-bold text-danger" id="display-difference">Rp 0</h3>
                        <span id="status-badge" class="badge bg-danger">Not Balanced</span>
                    </div>
                </div>
            </div>
            {{-- Progress Bar Visual --}}
            <div class="progress" style="height: 5px;">
                <div id="recon-progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        {{-- ERROR FLASH --}}
        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- WORKSPACE --}}
        <div class="row g-4">
            {{-- KOLOM KIRI: DEPOSITS (DEBIT) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light fw-bold text-success border-bottom-0">
                        <i class="bi bi-arrow-down-circle-fill me-1"></i> Setoran & Pemasukan (Debit)
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-recon mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="5%"><input type="checkbox" class="form-check-input" id="check-all-debit"></th>
                                    <th>Tgl</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    // Gabungkan cleared dan unreconciled untuk looping
                                    $allDeposits = $cleared_deposits->merge($unreconciled_deposits)->sortBy('entry_date');
                                @endphp
                                
                                @forelse ($allDeposits as $entry)
                                    @php 
                                        $isChecked = in_array($entry->ledger_id, $cleared_deposits->pluck('ledger_id')->toArray());
                                    @endphp
                                    <tr class="{{ $isChecked ? 'bg-matched' : '' }}">
                                        <td>
                                            <input class="form-check-input recon-check check-debit" 
                                                type="checkbox" 
                                                name="cleared_entries[]" 
                                                value="{{ $entry->ledger_id }}" 
                                                data-amount="{{ $entry->debit }}" 
                                                data-type="debit"
                                                {{ $isChecked ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $entry->entry_date->format('d/m/y') }}</td>
                                        <td class="small text-muted">{{ Str::limit($entry->description, 40) }}</td>
                                        <td class="text-end amount-col text-success">
                                            {{ number_format($entry->debit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data pemasukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between fw-bold">
                        <span>Total Dipilih:</span>
                        <span class="text-success" id="sum-debit">Rp 0</span>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: PAYMENTS (CREDIT) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light fw-bold text-danger border-bottom-0">
                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Cek & Pembayaran (Kredit)
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-recon mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="5%"><input type="checkbox" class="form-check-input" id="check-all-credit"></th>
                                    <th>Tgl</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $allPayments = $cleared_payments->merge($unreconciled_payments)->sortBy('entry_date');
                                @endphp

                                @forelse ($allPayments as $entry)
                                    @php 
                                        $isChecked = in_array($entry->ledger_id, $cleared_payments->pluck('ledger_id')->toArray());
                                    @endphp
                                    <tr class="{{ $isChecked ? 'bg-matched' : '' }}">
                                        <td>
                                            <input class="form-check-input recon-check check-credit" 
                                                type="checkbox" 
                                                name="cleared_entries[]" 
                                                value="{{ $entry->ledger_id }}" 
                                                data-amount="{{ $entry->credit }}" 
                                                data-type="credit"
                                                {{ $isChecked ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $entry->entry_date->format('d/m/y') }}</td>
                                        <td class="small text-muted">{{ Str::limit($entry->description, 40) }}</td>
                                        <td class="text-end amount-col text-danger">
                                            {{ number_format($entry->credit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data pembayaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between fw-bold">
                        <span>Total Dipilih:</span>
                        <span class="text-danger" id="sum-credit">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        <div class="mt-4 alert alert-secondary border-0 d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-info-circle-fill me-2"></i>
                Ada transaksi bank (Biaya Admin/Bunga) yang belum masuk?
            </div>
            <a href="{{ route('manual-journals.create') }}" target="_blank" class="btn btn-sm btn-dark">
                <i class="bi bi-plus-lg"></i> Buat Jurnal Penyesuaian
            </a>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. INITIAL VARIABLES ---
    const statementBalance = {{ $bankReconciliation->statement_balance }};
    
    // Kalkulasi Opening Balance (Logic Controller Baru)
    // $calcOpeningBalance dikirim dari Controller. Jika null, gunakan 0.
    // Jika Anda belum update controller, gunakan fallback logic sebelumnya.
    const openingBalance = {{ $calcOpeningBalance ?? ($bankReconciliation->closing_balance - ($cleared_deposits->sum('debit') - $cleared_payments->sum('credit'))) }};
    
    const checkboxes = document.querySelectorAll('.recon-check');
    const displayCleared = document.getElementById('display-cleared');
    const displayDifference = document.getElementById('display-difference');
    const sumDebitEl = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const btnFinish = document.getElementById('btn-finish');
    const statusBadge = document.getElementById('status-badge');

    // Format Currency Helper
    const fmt = (num) => new Intl.NumberFormat('id-ID').format(num);

    function calculate() {
        let totalDebit = 0;
        let totalCredit = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) {
                // Highlight row
                cb.closest('tr').classList.add('bg-matched');
                
                const amt = parseFloat(cb.dataset.amount);
                if (cb.dataset.type === 'debit') totalDebit += amt;
                else totalCredit += amt;
            } else {
                cb.closest('tr').classList.remove('bg-matched');
            }
        });

        // Kalkulasi Inti
        const currentClearedBalance = openingBalance + totalDebit - totalCredit;
        const difference = statementBalance - currentClearedBalance;

        // Update UI Text
        displayCleared.innerText = fmt(currentClearedBalance);
        sumDebitEl.innerText = 'Rp ' + fmt(totalDebit);
        sumCreditEl.innerText = 'Rp ' + fmt(totalCredit);

        // Tampilan Selisih
        const diffAbs = Math.abs(difference);
        displayDifference.innerText = 'Rp ' + fmt(difference);

        // Logika Validasi (Toleransi 1 rupiah untuk floating point)
        if (diffAbs < 1) {
            // BALANCE
            displayDifference.classList.remove('text-danger');
            displayDifference.classList.add('text-success');
            
            statusBadge.className = 'badge bg-success';
            statusBadge.innerHTML = '<i class="bi bi-check-lg"></i> Balanced';
            
            if(btnFinish) btnFinish.disabled = false;
            document.getElementById('recon-progress').className = 'progress-bar bg-success';
            document.getElementById('recon-progress').style.width = '100%';

        } else {
            // NOT BALANCE
            displayDifference.classList.remove('text-success');
            displayDifference.classList.add('text-danger');
            
            statusBadge.className = 'badge bg-danger';
            statusBadge.innerHTML = 'Not Balanced';
            
            if(btnFinish) btnFinish.disabled = true;
            document.getElementById('recon-progress').className = 'progress-bar bg-warning';
            document.getElementById('recon-progress').style.width = '50%'; // Indikasi belum selesai
        }
    }

    // Event Listeners
    checkboxes.forEach(cb => cb.addEventListener('change', calculate));

    // Check All Listeners
    document.getElementById('check-all-debit').addEventListener('change', function() {
        document.querySelectorAll('.check-debit').forEach(cb => cb.checked = this.checked);
        calculate();
    });
    document.getElementById('check-all-credit').addEventListener('change', function() {
        document.querySelectorAll('.check-credit').forEach(cb => cb.checked = this.checked);
        calculate();
    });

    // SweetAlert Confirmation for Finish
    const form = document.getElementById('recon-form');
    if(btnFinish) {
        btnFinish.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Selesaikan Rekonsiliasi?',
                text: "Saldo sudah cocok. Aksi ini akan mengunci periode rekonsiliasi ini.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Posting!',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Append hidden input action because we prevented default submit
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'action';
                    input.value = 'reconcile';
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    }

    // Notifikasi Flash
    @if(session('success')) Swal.fire('Berhasil!', "{{ session('success') }}", 'success'); @endif
    @if(session('error')) Swal.fire('Gagal!', "{{ session('error') }}", 'error'); @endif

    // Run First Calculation
    calculate();
});
</script>
@endpush