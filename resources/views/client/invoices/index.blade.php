@extends('layouts.client')

@section('content')
    <h2 class="fw-bold mb-4">Riwayat Invoice</h2>
    
    {{-- Notifikasi (dari redirect pembayaran Midtrans) --}}
    @if(request()->has('payment_success'))
        <div class="alert alert-success">
            Pembayaran Anda berhasil dan sedang diproses. Status akan segera diperbarui.
        </div>
    @endif
    @if(request()->has('payment_pending'))
        <div class="alert alert-info">
            Pembayaran Anda tertunda (pending). Kami akan memperbarui status invoice setelah pembayaran Anda selesai.
        </div>
    @endif

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('client.invoices.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari No. Invoice</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Contoh: INV/..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label for="order_date_filter" class="form-label">Tgl. Terbit</label>
                    <select name="order_date_filter" id="order_date_filter" class="form-select form-select-sm">
                        <option value="">-- Semua --</option>
                        @foreach($uniqueOrderDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('order_date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="col-md-2">
                    <label for="due_date_filter" class="form-label">Tgl. Jatuh Tempo</label>
                    <select name="due_date_filter" id="due_date_filter" class="form-select form-select-sm">
                        <option value="">-- Semua --</option>
                        @foreach($uniqueDueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('due_date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="col-md-2">
                    <label for="status_filter" class="form-label">Status</label>
                    <select name="status_filter" id="status_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="unpaid" @selected(request('status_filter') == 'unpaid')>Belum Lunas</option>
                        <option value="partially_paid" @selected(request('status_filter') == 'partially_paid')>Cicil</option>
                        <option value="paid" @selected(request('status_filter') == 'paid')>Lunas</option>
                        <option value="cancelled" @selected(request('status_filter') == 'cancelled')>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                    <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    {{-- ======================== --}}


    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal Terbit</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Sisa Tagihan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            {{-- ====================================================== --}}
                            {{-- ✅ INI ADALAH BLOK YANG HILANG DI FILE ANDA --}}
                            {{-- ====================================================== --}}
                            @php
                                // Gunakan accessor yang sudah benar (sudah di-load oleh controller)
                                $sisaTagihan = $invoice->remaining_balance;
                            @endphp
                            {{-- ====================================================== --}}
                            <tr>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ optional($invoice->order_date)->format('d M Y') }}</td>
                                <td class="{{ optional($invoice->due_date)->isPast() && !in_array($invoice->status, ['paid', 'cancelled']) ? 'text-danger fw-bold' : '' }}">
                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                </td>
                                <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold {{ $sisaTagihan > 0.01 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($invoice->status == 'paid')
                                        <span class="badge bg-success">Lunas</span>
                                    @elseif($invoice->status == 'partially_paid')
                                        <span class="badge bg-info text-dark">Cicil</span>
                                    @elseif($invoice->status == 'cancelled')
                                        <span class="badge bg-dark">Dibatalkan</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada invoice yang cocok dengan filter Anda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{-- appends() akan meneruskan semua parameter filter, termasuk 'sort' --}}
                {{ $invoices->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection