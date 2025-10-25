@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Laporan Keuangan</h2>

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
                <strong>Laba Rugi & Arus Kas:</strong> Berdasarkan rentang tanggal yang dipilih.<br>
                <strong>Neraca:</strong> Merupakan "foto" kondisi keuangan pada <strong>Tanggal Akhir</strong> yang dipilih.
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
                                <tr>
                                    <td class="fw-semibold ps-3" colspan="2">Aset Lancar</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Kas & Bank (Estimasi)</td>
                                    <td class="text-end pe-3">Rp {{ number_format($aset_kasDanBank, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Piutang Usaha</td>
                                    <td class="text-end pe-3">Rp {{ number_format($aset_piutangUsaha, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Persediaan Barang</td>
                                    <td class="text-end pe-3">Rp {{ number_format($aset_persediaan, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td class="fw-bold ps-4">Total Aset Lancar</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalAsetLancar, 0, ',', '.') }}</td>
                                </tr>
                                
                                <tr>
                                    <td class="fw-semibold ps-3" colspan="2">Aset Tetap</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Nilai Perolehan Aset</td>
                                    <td class="text-end pe-3">Rp {{ number_format($aset_tetap, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td class="fw-bold ps-4">Total Aset Tetap</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalAsetTetap, 0, ',', '.') }}</td>
                                </tr>
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
                                    <td class="fw-semibold ps-3" colspan="2">Liabilitas Lancar</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Utang Usaha (Supplier)</td>
                                    <td class="text-end pe-3">Rp {{ number_format($liabilitas_utangUsaha, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td class="fw-bold ps-4">Total Liabilitas Lancar</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalLiabilitasLancar, 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold ps-3" colspan="2">Liabilitas Jangka Panjang</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Utang Pinjaman</td>
                                    <td class="text-end pe-3">Rp {{ number_format($liabilitas_utangJangkaPanjang, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td class="fw-bold ps-4">Total Liabilitas Jangka Panjang</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalLiabilitasJangkaPanjang, 0, ',', '.') }}</td>
                                </tr>
                                
                                <tr class="table-secondary">
                                    <td class="fw-bold ps-3">TOTAL LIABILITAS</td>
                                    <td class="text-end fw-bold pe-3">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                                </tr>
                                
                                <tr>
                                    <td class="fw-semibold ps-3" colspan="2">Ekuitas (Modal)</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Modal Disetor</td>
                                    <td class="text-end pe-3">Rp {{ number_format($ekuitas_modalDisetor, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4">Penarikan Modal (Prive)</td>
                                    <td class="text-end pe-3 text-danger">(Rp {{ number_format($ekuitas_penarikanModal, 0, ',', '.') }})</td>
                                </tr>
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
            @if(round($selisih) != 0)
                <div class="card-footer bg-danger text-white text-center fw-bold">
                    TIDAK SEIMBANG! (Selisih: Rp {{ number_format($selisih, 0, ',', '.') }}) - Cek kembali semua transaksi.
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
                            <td class="fw-semibold" style="width: 60%;">A. Pendapatan Kotor Penjualan</td>
                            <td class="text-end" style="width: 40%;">Rp {{ number_format($pendapatanKotor, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Potongan Diskon Penjualan</td>
                            <td class="text-end text-danger">(Rp {{ number_format($totalDiskonPenjualan, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Retur Penjualan</td>
                            <td class="text-end text-danger">(Rp {{ number_format($totalReturPenjualan, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold ps-4">Pendapatan Bersih (Netto)</td>
                            <td class="text-end fw-bold">Rp {{ number_format($pendapatanNetto, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">B. Harga Pokok Penjualan (HPP)</td>
                            <td class="text-end text-danger">(Rp {{ number_format($totalHPP, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">LABA KOTOR (Gross Profit)</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            {{-- BEBAN OPERASIONAL --}}
                         <tr>
                            <td class="fw-semibold ps-3" colspan="2">C. Beban Operasional</td>
                        </tr>
                        {{-- Detail Beban --}}
                        <tr>
                            <td class="ps-4">Beban Usaha Lainnya (Listrik, Gaji, dll)</td>
                            <td class="text-end text-danger">(Rp {{ number_format($bebanDariExpenses, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Beban Bunga Pinjaman</td>
                            <td class="text-end text-danger">(Rp {{ number_format($bebanBungaPinjaman, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold ps-4">Total Beban Operasional</td>
                            <td class="text-end fw-bold text-danger">(Rp {{ number_format($totalBebanOperasional, 0, ',', '.') }})</td>
                        </tr>

                        {{-- LABA BERSIH (Tetap Sama) --}}
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
    {{-- LAPORAN ARUS KAS & UTANG/PIUTANG (Ringkasan) --}}
    {{-- =================================== --}}
    <div class="row g-4">
        {{-- ARUS KAS --}}
        <div class="col-lg-12">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan Arus Kas (Sederhana)</h5>
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
                                @forelse ($pemasukan as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td>Pembayaran Invoice <a href="{{ route('invoices.show', $item->invoice_id) }}">{{ $item->salesInvoice->invoice_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada kas masuk.</td></tr>
                                @endforelse
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
                                @forelse ($pengeluaranPO as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td>Pembayaran PO <a href="{{ route('purchase-orders.show', $item->po_id) }}">{{ $item->purchaseOrder->po_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Jangan tampilkan apa-apa jika kosong --}}
                                @endforelse
                                
                                {{-- Pengeluaran Beban (Sudah Ada) --}}
                                @foreach ($pengeluaranBeban as $item)
                                 <tr>
                                    <td>{{ optional($item->expense_date)->format('d/m/Y') }}</td>
                                    <td>Beban: {{ $item->category }} ({{ $item->description }})</td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                
                                {{-- ✅ [TAMBAHKAN LOOP INI] Pengeluaran Pinjaman --}}
                                @forelse ($pengeluaranPinjaman as $item)
                                 <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    {{-- Link ke detail pinjaman induk --}}
                                    <td>Pembayaran Cicilan <a href="{{ route('loans.show', $item->loan_id) }}">{{ $item->loan->lender_name ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->total_paid, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                {{-- Jangan tampilkan apa-apa jika kosong --}}
                                @endforelse
                                {{-- Akhir Loop Baru --}}

                                {{-- Total Pengeluaran (Sudah Ada) --}}
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
    </div>
</div>
@endsection