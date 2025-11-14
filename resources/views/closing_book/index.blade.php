@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Proses Tutup Buku (Jurnal Penutup)</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Perhatian!</h4>
                        <p>Proses ini akan **meng-nol-kan** semua saldo di akun Pendapatan, HPP, dan Beban untuk tahun yang Anda pilih, dan memindahkan total Laba/Rugi Bersih ke akun Ekuitas (Laba Ditahan).</p>
                        <hr>
                        <p class="mb-0">Aksi ini **tidak dapat dibatalkan** secara otomatis. Pastikan Anda sudah yakin semua transaksi (termasuk penyusutan) untuk tahun tersebut telah selesai dicatat.</p>
                    </div>

                    <form action="{{ route('closing-book.store') }}" method="POST" id="closing-book-form">
                        @csrf
                        <div class="mb-3">
                            <label for="year" class="form-label fs-5 fw-semibold">Pilih Tahun yang Akan Ditutup</label>
                            <select name="year" id="year" class="form-select form-select-lg">
                                @forelse ($availableYears as $year)
                                    @php
                                        $isClosed = in_array($year, $closedYears);
                                    @endphp
                                    <option value="{{ $year }}" {{ $isClosed ? 'disabled' : '' }}>
                                        Tahun {{ $year }} {{ $isClosed ? '(Sudah Ditutup)' : '' }}
                                    </option>
                                @empty
                                    <option disabled>Tidak ada data.</option>
                                @endforelse
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg" id="btn-submit-closing">
                                <i class="bi bi-lock-fill"></i> Proses Tutup Buku Tahun <span id="selected-year-text">{{ $availableYears[0] ?? '' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const yearSelect = document.getElementById('year');
    const submitButton = document.getElementById('btn-submit-closing');
    const yearText = document.getElementById('selected-year-text');

    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            yearText.textContent = this.value;
        });
    }

    document.getElementById('closing-book-form').addEventListener('submit', function(e) {
        const selectedYear = yearSelect.value;
        const confirmation = confirm(`Anda yakin ingin menutup buku untuk tahun ${selectedYear}? Aksi ini tidak dapat dibatalkan.`);
        
        if (!confirmation) {
            e.preventDefault();
        } else {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
        }
    });
});
</script>
@endpush