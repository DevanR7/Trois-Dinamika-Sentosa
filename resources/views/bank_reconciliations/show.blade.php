@extends('layouts.app')

@section('styles')
<style>
    /* Agar kartu ringkasan tetap terlihat saat scroll tabel panjang */
    .sticky-top-card { position: sticky; top: 20px; z-index: 1000; }
    
    /* Styling tabel agar lebih padat dan rapi */
    .table-recon th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .table-recon td { font-size: 0.9rem; vertical-align: middle; }
    
    /* Font Monospace untuk angka agar sejajar */
    .amount-col { font-family: 'Courier New', Courier, monospace; font-weight: 600; }
    
    /* Highlight baris yang dicentang */
    .bg-matched { background-color: #d1e7dd !important; transition: background-color 0.3s; } 
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
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-bank2 me-2"></i>{{ $bankReconciliation->account->account_name }}
                </h4>
                <span class="badge bg-secondary">Periode Laporan: {{ $bankReconciliation->statement_date->format('d F Y') }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                
                @if($bankReconciliation->status == 'draft')
                    <button type="submit" name="action" value="save_draft" class="btn btn-dark">
                        <i class="bi bi-save"></i> Simpan Draft
                    </button>
                    {{-- Tombol ini akan aktif otomatis via JS jika selisih 0 --}}
                    <button type="submit" name="action" value="reconcile" id="btn-finish" class="btn btn-success" disabled>
                        <i class="bi bi-check-circle-fill me-1"></i> Selesaikan & Posting
                    </button>
                @else
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-lock-fill"></i> Status: Reconciled (Terkunci)
                    </span>
                @endif
            </div>
        </div>

        {{-- SUMMARY CARD (STICKY) --}}
        {{-- Kartu ini akan menempel di atas saat user scroll ke bawah --}}
        <div class="card shadow border-0 mb-4 sticky-top-card">
            <div class="card-body py-3">
                <div class="row align-items-center text-center">
                    {{-- 1. TARGET (SALDO BANK) --}}
                    <div class="col-md-3 border-end">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Saldo Rekening Koran</small>
                        <h3 class="mb-0 fw-bold text-primary">Rp <span id="display-statement">{{ number_format($bankReconciliation->statement_balance, 0, ',', '.') }}</span></h3>
                    </div>

                    {{-- 2. KALKULASI LIVE (SALDO SISTEM) --}}
                    <div class="col-md-1 text-muted"><i class="bi bi-dash-lg fs-2"></i></div>
                    <div class="col-md-4 border-end">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Saldo Sistem (Cleared)</small>
                        <h3 class="mb-0 fw-bold text-dark">Rp <span id="display-cleared">0</span></h3>
                        <small class="text-muted fst-italic" style="font-size: 0.75rem;">(Saldo Awal + Deposit Dicentang - Pembayaran Dicentang)</small>
                    </div>
                    
                    {{-- 3. SELISIH --}}
                    <div class="col-md-1 text-muted"><i class="bi bi-pause fs-2" style="transform: rotate(90deg);"></i></div>
                    <div class="col-md-3">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Selisih (Target: 0)</small>
                        <h3 class="mb-0 fw-bold text-danger" id="display-difference">Rp 0</h3>
                        <span id="status-badge" class="badge bg-danger">Not Balanced</span>
                    </div>
                </div>
            </div>
            {{-- Progress Bar Visual --}}
            <div class="progress" style="height: 6px;">
                <div id="recon-progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        {{-- ERROR FLASH --}}
        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Terjadi Kesalahan:</strong>
            <ul class="mb-0 mt-1 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- WORKSPACE --}}
        <div class="row g-4">
            
            {{-- KOLOM KIRI: DEPOSITS (DEBIT / UANG MASUK) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-success text-white border-bottom-0 d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-arrow-down-circle-fill me-2"></i> Setoran & Pemasukan (Debit)</span>
                        <span class="badge bg-white text-success" id="sum-debit">Rp 0</span>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-recon mb-0">
                            <thead class="table-light sticky-top shadow-sm">
                                <tr>
                                    <th width="5%" class="text-center"><input type="checkbox" class="form-check-input" id="check-all-debit"></th>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    // Gabungkan cleared dan unreconciled, urutkan by tanggal
                                    $allDeposits = $cleared_deposits->merge($unreconciled_deposits)->sortBy('entry_date');
                                @endphp
                                
                                @forelse ($allDeposits as $entry)
                                    @php 
                                        $isChecked = in_array($entry->ledger_id, $cleared_deposits->pluck('ledger_id')->toArray());
                                    @endphp
                                    <tr class="{{ $isChecked ? 'bg-matched' : '' }}">
                                        <td class="text-center">
                                            <input class="form-check-input recon-check check-debit" 
                                                type="checkbox" 
                                                name="cleared_entries[]" 
                                                value="{{ $entry->ledger_id }}" 
                                                data-amount="{{ $entry->debit }}" 
                                                data-type="debit"
                                                {{ $isChecked ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $entry->entry_date->format('d/m/y') }}</td>
                                        <td class="small text-secondary">{{ Str::limit($entry->description, 45) }}</td>
                                        <td class="text-end amount-col text-success">
                                            {{ number_format($entry->debit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">Tidak ada data pemasukan pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: PAYMENTS (CREDIT / UANG KELUAR) --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-danger text-white border-bottom-0 d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-arrow-up-circle-fill me-2"></i> Cek & Pembayaran (Kredit)</span>
                        <span class="badge bg-white text-danger" id="sum-credit">Rp 0</span>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-recon mb-0">
                            <thead class="table-light sticky-top shadow-sm">
                                <tr>
                                    <th width="5%" class="text-center"><input type="checkbox" class="form-check-input" id="check-all-credit"></th>
                                    <th>Tanggal</th>
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
                                        <td class="text-center">
                                            <input class="form-check-input recon-check check-credit" 
                                                type="checkbox" 
                                                name="cleared_entries[]" 
                                                value="{{ $entry->ledger_id }}" 
                                                data-amount="{{ $entry->credit }}" 
                                                data-type="credit"
                                                {{ $isChecked ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $entry->entry_date->format('d/m/y') }}</td>
                                        <td class="small text-secondary">{{ Str::limit($entry->description, 45) }}</td>
                                        <td class="text-end amount-col text-danger">
                                            {{ number_format($entry->credit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">Tidak ada data pembayaran pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER INFO & ACTION --}}
        <div class="mt-4 card shadow-sm border-warning">
            <div class="card-body bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                    <strong>Tips:</strong> Ada transaksi bank (Biaya Admin/Bunga) yang belum tercatat di sistem?
                </div>
                <a href="{{ route('manual-journals.create') }}" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">
                    <i class="bi bi-plus-lg"></i> Buat Jurnal Penyesuaian
                </a>
            </div>
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
    
    // Saldo Awal (Opening Balance)
    // Diambil dari variable $calcOpeningBalance dari Controller. 
    // Jika tidak ada (null), fallback ke perhitungan manual (Closing - Mutasi Cleared).
    const openingBalance = {{ $calcOpeningBalance ?? ($bankReconciliation->closing_balance - ($cleared_deposits->sum('debit') - $cleared_payments->sum('credit'))) }};
    
    const checkboxes = document.querySelectorAll('.recon-check');
    const displayCleared = document.getElementById('display-cleared');
    const displayDifference = document.getElementById('display-difference');
    const sumDebitEl = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const btnFinish = document.getElementById('btn-finish');
    const statusBadge = document.getElementById('status-badge');
    const reconProgress = document.getElementById('recon-progress');

    // Helper Format Rupiah (ID-ID)
    const fmt = (num) => new Intl.NumberFormat('id-ID').format(num);

    // --- 2. CORE CALCULATION LOGIC ---
    function calculate() {
        let totalDebit = 0;
        let totalCredit = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) {
                cb.closest('tr').classList.add('bg-matched');
                const amt = parseFloat(cb.dataset.amount);
                if (cb.dataset.type === 'debit') totalDebit += amt;
                else totalCredit += amt;
            } else {
                cb.closest('tr').classList.remove('bg-matched');
            }
        });

        // Rumus: Saldo Awal + Pemasukan Tercentang - Pengeluaran Tercentang
        const currentClearedBalance = openingBalance + totalDebit - totalCredit;
        const difference = statementBalance - currentClearedBalance;

        // Update UI Text
        displayCleared.innerText = fmt(currentClearedBalance);
        sumDebitEl.innerText = '+ Rp ' + fmt(totalDebit);
        sumCreditEl.innerText = '- Rp ' + fmt(totalCredit);

        // Tampilan Selisih
        const diffAbs = Math.abs(difference);
        displayDifference.innerText = 'Rp ' + fmt(difference);

        // Logika Validasi (Toleransi 1 rupiah utk floating point error)
        if (diffAbs < 1) {
            // STATUS: BALANCE (SEIMBANG)
            displayDifference.classList.remove('text-danger');
            displayDifference.classList.add('text-success');
            
            statusBadge.className = 'badge bg-success';
            statusBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i> SEIMBANG (MATCH)';
            
            if(btnFinish) btnFinish.disabled = false; // Hidupkan tombol
            
            reconProgress.className = 'progress-bar bg-success progress-bar-striped progress-bar-animated';
            reconProgress.style.width = '100%';

        } else {
            // STATUS: NOT BALANCE
            displayDifference.classList.remove('text-success');
            displayDifference.classList.add('text-danger');
            
            statusBadge.className = 'badge bg-danger';
            statusBadge.innerHTML = '<i class="bi bi-x-circle-fill"></i> BELUM SEIMBANG';
            
            if(btnFinish) btnFinish.disabled = true; // Matikan tombol
            
            reconProgress.className = 'progress-bar bg-danger';
            reconProgress.style.width = '50%'; 
        }
    }

    // --- 3. EVENT LISTENERS ---
    checkboxes.forEach(cb => cb.addEventListener('change', calculate));

    // Check All (Debit)
    const checkAllDebit = document.getElementById('check-all-debit');
    if(checkAllDebit){
        checkAllDebit.addEventListener('change', function() {
            document.querySelectorAll('.check-debit').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }

    // Check All (Credit)
    const checkAllCredit = document.getElementById('check-all-credit');
    if(checkAllCredit){
        checkAllCredit.addEventListener('change', function() {
            document.querySelectorAll('.check-credit').forEach(cb => cb.checked = this.checked);
            calculate();
        });
    }

    // --- 4. SWEETALERT CONFIRMATION ---
    const form = document.getElementById('recon-form');
    if(btnFinish) {
        btnFinish.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Selesaikan Rekonsiliasi?',
                html: "Saldo sudah cocok (Balance).<br>Aksi ini akan <b>mengunci (lock)</b> periode rekonsiliasi ini.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Posting!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tambahkan input hidden untuk action
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

    // --- 5. NOTIFIKASI FLASH ---
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" });
    @endif

    // Run First Calculation
    calculate();
});
</script>
@endpush