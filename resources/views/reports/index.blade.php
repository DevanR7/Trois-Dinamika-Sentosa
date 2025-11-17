@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Laporan Keuangan (Berbasis Jurnal Umum)</h2>
    
    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Tampilkan</button>
                </div>
            </form>
        </div>
        <div class="card-footer bg-light">
            <small class="text-muted">
                <strong>Laba Rugi:</strong> Berdasarkan Jurnal Umum pada rentang tanggal yang dipilih.<br>
                <strong>Neraca:</strong> Merupakan "foto" Jurnal Umum pada <strong>Tanggal Akhir</strong> yang dipilih.
            </small>
        </div>
    </div>

    {{-- =================================== --}}
    {{-- LAPORAN NERACA (BALANCE SHEET) --}}
    {{-- =================================== --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Laporan Neraca (Balance Sheet)</h5>
            <span>Per Tanggal: {{ $endDateCarbon->isoFormat('D MMMM Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                
                {{-- SISI ASET --}}
                <div class="col-lg-6 border-end">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold fs-5 p-3" colspan="2">ASET</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Loop Dinamis Akun Aset --}}
                                @forelse ($neraca_aset as $account)
                                <tr>
                                    <td class="ps-4">{{ $account->account_name }}</td>
                                    <td class="text-end pe-3">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="ps-4 text-muted" colspan="2">Tidak ada data Aset.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-primary">
                                <tr>
                                    <td class="fw-bold fs-5 p-3">TOTAL ASET</td>
                                    <td class="text-end fw-bold fs-5 p-3">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- SISI LIABILITAS & EKUITAS --}}
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold fs-5 p-3" colspan="2">LIABILITAS & EKUITAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold ps-3" colspan="2">Liabilitas</td>
                                </tr>
                                {{-- Loop Dinamis Akun Liabilitas --}}
                                @forelse ($neraca_liabilitas as $account)
                                <tr>
                                    <td class="ps-4">{{ $account->account_name }}</td>
                                    <td class="text-end pe-3">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="ps-4 text-muted" colspan="2">Tidak ada data Liabilitas.</td>
                                </tr>
                                @endforelse
                                <tr class="table-secondary">
                                    <td class="fw-bold ps-3">TOTAL LIABILITAS</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                                </tr>
                                
                                <tr>
                                    <td class="fw-semibold ps-3 mt-2" colspan="2">Ekuitas (Modal)</td>
                                </tr>
                                {{-- Loop Dinamis Akun Ekuitas (Modal Setor, Prive, dll) --}}
                                @forelse ($neraca_ekuitas_non_pl as $account)
                                <tr>
                                    <td class="ps-4">{{ $account->account_name }}</td>
                                    <td class="text-end pe-3">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="ps-4 text-muted" colspan="2">Tidak ada data Ekuitas.</td>
                                </tr>
                                @endforelse
                                
                                {{-- Laba Rugi Akumulasi (Dihitung terpisah) --}}
                                <tr>
                                    <td class="ps-4">Laba/Rugi Akumulasi</td>
                                    <td class="text-end pe-3 {{ $ekuitas_labaRugiAkumulasi < 0 ? 'text-danger' : '' }}">
                                        @if($ekuitas_labaRugiAkumulasi < 0)
                                            (Rp {{ number_format(abs($ekuitas_labaRugiAkumulasi), 0, ',', '.') }})
                                        @else
                                            Rp {{ number_format($ekuitas_labaRugiAkumulasi, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr class="table-secondary">
                                    <td class="fw-bold ps-3">TOTAL EKUITAS</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-primary">
                                <tr>
                                    <td class="fw-bold fs-5 p-3">TOTAL LIABILITAS & EKUITAS</td>
                                    <td class="text-end fw-bold fs-5 p-3">Rp {{ number_format($totalLiabilitasDanEkuitas, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Pengecekan Keseimbangan --}}
            @php
                $selisih = $totalAset - $totalLiabilitasDanEkuitas;
            @endphp
            @if(round($selisih, 2) != 0)
                <div class="card-footer bg-danger text-white text-center fw-bold">
                    TIDAK SEIMBANG! (Selisih: Rp {{ number_format($selisih, 2, ',', '.') }}) - Cek kembali Jurnal Umum.
                </div>
            @else
                <div class="card-footer bg-success text-white text-center fw-bold">
                    SEIMBANG (BALANCE)
                </div>
            @endif
        </div>
    </div>

    {{-- =================================== --}}
    {{-- LAPORAN LABA RUGI --}}
    {{-- =================================== --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Laporan Laba Rugi</h5>
            <span>{{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold" style="width: 60%;" colspan="2">A. Pendapatan</td>
                        </tr>
                        {{-- Loop Dinamis Akun Pendapatan --}}
                        @forelse ($labaRugi_pendapatan as $account)
                        <tr>
                            <td class="ps-4" style="width: 60%;">{{ $account->account_name }}</td>
                            <td class="text-end" style="width: 40%;">Rp {{ number_format($account->total_credit - $account->total_debit, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-muted" colspan="2">Tidak ada data Pendapatan.</td>
                        </tr>
                        @endforelse
                        <tr class="table-light">
                            <td class="fw-bold ps-4">Total Pendapatan</td>
                            <td class="text-end fw-bold">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                        </tr>

                        <tr>
                            <td class="fw-semibold" colspan="2">B. Harga Pokok Penjualan (HPP)</td>
                        </tr>
                        {{-- Loop Dinamis Akun HPP --}}
                        @forelse ($labaRugi_hpp as $account)
                        <tr>
                            <td class="ps-4">{{ $account->account_name }}</td>
                            <td class="text-end text-danger">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-muted" colspan="2">Tidak ada data HPP.</td>
                        </tr>
                        @endforelse
                        <tr class="table-light">
                            <td class="fw-bold ps-4">Total HPP</td>
                            <td class="text-end fw-bold text-danger">(Rp {{ number_format($totalHPP, 0, ',', '.') }})</td>
                        </tr>

                        <tr class="table-info">
                            <td class="fw-bold">LABA KOTOR (Gross Profit)</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
                        </tr>
                         
                        <tr>
                            <td class="fw-semibold ps-3" colspan="2">C. Beban Operasional</td>
                        </tr>
                        {{-- Loop Dinamis Akun Beban --}}
                        @forelse ($labaRugi_beban as $account)
                        <tr>
                            <td class="ps-4">{{ $account->account_name }}</td>
                            <td class="text-end text-danger">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="ps-4 text-muted" colspan="2">Tidak ada data Beban.</td>
                        </tr>
                        @endforelse
                        <tr class="table-light">
                            <td class="fw-bold ps-4">Total Beban Operasional</td>
                            <td class="text-end fw-bold text-danger">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</td>
                        </tr>
                        
                        <tr class="table-dark">
                            <td class="fw-bold fs-5">LABA BERSIH (Net Profit)</td>
                            <td class="text-end fw-bold fs-5">
                                @if($labaBersih < 0)
                                    (Rp {{ number_format(abs($labaBersih), 0, ',', '.') }})
                                @else
                                    Rp {{ number_format($labaBersih, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- =================================== --}}
    {{-- LAPORAN PENDUKUNG (Sub-Ledger & Arus Kas) --}}
    {{-- =================================== --}}
    <h3 class="fw-bold mb-3 mt-5">Laporan Pendukung</h3>
    <div class="row g-4">
        
        {{-- LAPORAN ARUS KAS (METODE TIDAK LANGSUNG) -- BARU DITAMBAHKAN --}}
        <div class="col-lg-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan Arus Kas (Metode Tidak Langsung)</h5>
                    <span class="badge bg-light text-success">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <tbody>
                                {{-- 1. AKTIVITAS OPERASI --}}
                                <tr class="table-light fw-bold"><td colspan="2">1. Arus Kas dari Aktivitas Operasi</td></tr>
                                <tr>
                                    <td class="ps-4">Laba Bersih</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($cf_operating_net_income, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted fst-italic">Penyesuaian untuk item non-kas:</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">+ Penyusutan Aset Tetap</td>
                                    <td class="text-end">Rp {{ number_format($cf_operating_depreciation, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted fst-italic">Perubahan Modal Kerja:</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Penurunan (Kenaikan) Piutang Usaha</td>
                                    <td class="text-end {{ $cf_change_ar < 0 ? 'text-danger' : '' }}">Rp {{ number_format($cf_change_ar, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Penurunan (Kenaikan) Persediaan</td>
                                    <td class="text-end {{ $cf_change_inventory < 0 ? 'text-danger' : '' }}">Rp {{ number_format($cf_change_inventory, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Penurunan (Kenaikan) Deposit ke Supplier</td>
                                    <td class="text-end {{ $cf_change_supplier_deposit < 0 ? 'text-danger' : '' }}">Rp {{ number_format($cf_change_supplier_deposit, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kenaikan (Penurunan) Hutang Dagang</td>
                                    <td class="text-end {{ $cf_change_ap < 0 ? 'text-danger' : '' }}">Rp {{ number_format($cf_change_ap, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kenaikan (Penurunan) Deposit dari Klien</td>
                                    <td class="text-end {{ $cf_change_client_deposit < 0 ? 'text-danger' : '' }}">Rp {{ number_format($cf_change_client_deposit, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-success border-top border-success">
                                    <td class="fw-bold ps-4">Kas Bersih dari Aktivitas Operasi</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($total_cash_from_operations, 0, ',', '.') }}</td>
                                </tr>

                                {{-- 2. AKTIVITAS INVESTASI --}}
                                <tr class="table-light fw-bold"><td colspan="2" class="pt-3">2. Arus Kas dari Aktivitas Investasi</td></tr>
                                <tr>
                                    <td class="ps-4">Pembelian Aset Tetap</td>
                                    <td class="text-end text-danger">(Rp {{ number_format($cf_investing_purchase_asset, 0, ',', '.') }})</td>
                                </tr>
                                <tr class="table-warning border-top border-warning">
                                    <td class="fw-bold ps-4">Kas Bersih untuk Aktivitas Investasi</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($total_cash_from_investing, 0, ',', '.') }}</td>
                                </tr>

                                {{-- 3. AKTIVITAS PENDANAAN --}}
                                <tr class="table-light fw-bold"><td colspan="2" class="pt-3">3. Arus Kas dari Aktivitas Pendanaan</td></tr>
                                <tr>
                                    <td class="ps-4">Setoran Modal</td>
                                    <td class="text-end">Rp {{ number_format($cf_financing_capital_in, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Penarikan Modal (Prive)</td>
                                    <td class="text-end text-danger">(Rp {{ number_format($cf_financing_drawing, 0, ',', '.') }})</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Penerimaan Pinjaman</td>
                                    <td class="text-end">Rp {{ number_format($cf_financing_loan_in, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Pelunasan Pokok Pinjaman</td>
                                    <td class="text-end text-danger">(Rp {{ number_format($cf_financing_loan_pay, 0, ',', '.') }})</td>
                                </tr>
                                <tr class="table-info border-top border-info">
                                    <td class="fw-bold ps-4">Kas Bersih dari Aktivitas Pendanaan</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($total_cash_from_financing, 0, ',', '.') }}</td>
                                </tr>

                                {{-- RINGKASAN --}}
                                <tr class="table-dark border-top border-dark mt-3">
                                    <td class="fw-bold ps-3 py-3">Kenaikan (Penurunan) Bersih Kas</td>
                                    <td class="text-end fw-bold py-3">Rp {{ number_format($net_increase_cash, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Saldo Kas Awal Periode</td>
                                    <td class="text-end">Rp {{ number_format($cash_beginning, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-secondary fw-bold fs-5">
                                    <td class="ps-3">Saldo Kas Akhir Periode</td>
                                    <td class="text-end text-primary">Rp {{ number_format($cash_ending, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ARUS KAS SEDERHANA (Basis Kas) --}}
        <div class="col-lg-12">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan Arus Kas (Sederhana - Basis Kas)</h5>
                    @php
                        $arusKasBersih = $totalPemasukan - $totalPengeluaran;
                    @endphp
                    <span class="fw-bold fs-5 {{ $arusKasBersih >= 0 ? 'text-success' : 'text-danger' }}">
                         @if($arusKasBersih < 0)
                            (Rp {{ number_format(abs($arusKasBersih), 0, ',', '.') }})
                        @else
                            Rp {{ number_format($arusKasBersih, 0, ',', '.') }}
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="3" class="fw-semibold">Kas Masuk (Pemasukan)</th>
                                </tr>
                                <tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($pemasukan_invoice as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td>Pembayaran Invoice <a href="{{ route('invoices.show', $item->invoice_id) }}">{{ $item->salesInvoice->invoice_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse
                                @forelse ($pemasukan_modal as $item)
                                <tr>
                                    <td>{{ optional($item->transaction_date)->format('d/m/Y') }}</td>
                                    <td>Setoran Modal: {{ $item->description }}</td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse
                                @if($pemasukan_invoice->isEmpty() && $pemasukan_modal->isEmpty())
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada kas masuk.</td></tr>
                                @endif
                                <tr class="table-light">
                                    <td colspan="2" class="text-end fw-bold">Total Pemasukan</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                            <thead class="table-light">
                                <tr>
                                    <th colspan="3" class="fw-semibold mt-3">Kas Keluar (Pengeluaran)</th>
                                </tr>
                                 <tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                             <tbody>
                                @forelse ($pengeluaran_po as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td>Pembayaran PO <a href="{{ route('purchase-orders.show', $item->po_id) }}">{{ $item->purchaseOrder->po_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse
                                
                                @foreach ($pengeluaran_beban as $item)
                                 <tr>
                                    <td>{{ optional($item->expense_date)->format('d/m/Y') }}</td>
                                    <td>Beban: {{ $item->expenseAccount->account_name ?? $item->category }} ({{ $item->description }})</td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                
                                @forelse ($pengeluaran_pinjaman as $item)
                                 <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td>Pembayaran Cicilan <a href="{{ route('loans.show', $item->loan_id) }}">{{ $item->loan->lender_name ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->total_paid, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse

                                @forelse ($pengeluaran_aset as $item)
                                 <tr>
                                    <td>{{ optional($item->purchase_date)->format('d/m/Y') }}</td>
                                    <td>Pembelian Aset: {{ $item->asset_name }}</td>
                                    <td class="text-end">Rp {{ number_format($item->purchase_cost, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse

                                @forelse ($pengeluaran_modal as $item)
                                 <tr>
                                    <td>{{ optional($item->transaction_date)->format('d/m/Y') }}</td>
                                    <td>Penarikan Modal: {{ $item->description }}</td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Kosong --}}
                                @endforelse

                                @if($totalPengeluaran == 0)
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada kas keluar.</td></tr>
                                @endif
                                
                                <tr class="table-light">
                                    <td colspan="2" class="text-end fw-bold">Total Pengeluaran</td>
                                    <td class="text-end fw-bold text-danger">(Rp {{ number_format($totalPengeluaran, 0, ',', '.') }})</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RINCIAN PIUTANG (Sub-Ledger) --}}
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold">Rincian Piutang Usaha (Sub-Ledger)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Klien</th>
                                    <th>Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-end">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporanPiutang as $invoice)
                                    <tr>
                                        <td>{{ $invoice->client->client_name ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice->invoice_id) }}">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                                        <td class="text-end fw-semibold">
                                            Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Tidak ada piutang jatuh tempo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer fw-bold d-flex justify-content-between">
                    <span>Total Piutang (Sub-Ledger)</span>
                    <span>Rp {{ number_format($totalPiutang_SL, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- RINCIAN UTANG (Sub-Ledger) --}}
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold">Rincian Utang Usaha (Sub-Ledger)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Supplier</th>
                                    <th>No. PO</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-end">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporanUtang as $po)
                                    <tr>
                                        <td>{{ $po->supplier->supplier_name ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('purchase-orders.show', $po->po_id) }}">
                                                {{ $po->po_number }}
                                            </a>
                                        </td>
                                        <td>{{ $po->due_date ? $po->due_date->format('d/m/Y') : '-' }}</td>
                                        <td class="text-end fw-semibold">
                                            Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Tidak ada utang jatuh tempo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer fw-bold d-flex justify-content-between">
                    <span>Total Utang (Sub-Ledger)</span>
                    <span>Rp {{ number_format($totalUtang_SL, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection