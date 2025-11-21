@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white p-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-bank2 me-2"></i>Mulai Rekonsiliasi Baru</h4>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-info border-0 bg-light d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
                        <div>
                            Siapkan <strong>Rekening Koran (Bank Statement)</strong> Anda. Masukkan saldo akhir yang tertera di rekening koran tersebut di bawah ini.
                        </div>
                    </div>

                    <form action="{{ route('bank-reconciliations.store') }}" method="POST" id="recon-create-form">
                        @csrf
                        <div class="row g-4">
                            {{-- Pilih Akun --}}
                            <div class="col-12">
                                <label for="company_bank_account_id" class="form-label fw-bold">Pilih Akun Bank <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="company_bank_account_id" name="company_bank_account_id" required>
                                    <option value="" disabled selected></option>
                                    @forelse ($bankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}" {{ old('company_bank_account_id') == $account->company_bank_account_id ? 'selected' : '' }}>
                                            {{ $account->account->account_number ?? '' }} - {{ $account->account->account_name ?? $account->bank_name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>Belum ada akun bank terdaftar</option>
                                    @endforelse
                                </select>
                                @error('company_bank_account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Tanggal Laporan --}}
                            <div class="col-md-6">
                                <label for="statement_date" class="form-label fw-bold">Tanggal Akhir Rekening Koran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" id="statement_date" name="statement_date" value="{{ old('statement_date', now()->endOfMonth()->toDateString()) }}" required>
                                <div class="form-text">Tanggal pisah batas transaksi (Cut-off).</div>
                                @error('statement_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            {{-- Saldo Akhir (DENGAN FORMAT RUPIAH) --}}
                            <div class="col-md-6">
                                <label for="statement_balance_display" class="form-label fw-bold">Saldo Akhir (di Bank) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Rp</span>
                                    
                                    {{-- Input Visual (Untuk User lihat ada titiknya) --}}
                                    <input type="text" class="form-control text-end" id="statement_balance_display" placeholder="0" required>
                                    
                                    {{-- Input Hidden (Untuk dikirim ke Database, angka murni) --}}
                                    <input type="hidden" name="statement_balance" id="statement_balance" value="{{ old('statement_balance') }}">
                                </div>
                                <div class="form-text">Nominal saldo akhir yang tertera di PDF/Kertas Bank.</div>
                                @error('statement_balance')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                            <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                                <i class="bi bi-arrow-right-circle-fill me-2"></i> Lanjut ke Proses Mencocokkan
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Init Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Akun Kas/Bank --',
            width: '100%'
        });

        // --- LOGIKA FORMAT RUPIAH ---
        const displayInput = document.getElementById('statement_balance_display');
        const hiddenInput = document.getElementById('statement_balance');

        function formatRupiah(angka) {
            let number_string = angka.toString().replace(/[^,\d-]/g, "").toString(), // Izinkan minus (-)
                split = number_string.split(","),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            // Tambahkan titik jika yang diinput sudah menjadi ribuan
            if (ribuan) {
                separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            
            // Tambahkan tanda minus di depan jika ada
            if (angka.toString().indexOf('-') !== -1 && rupiah.indexOf('-') === -1) {
                return '-' + rupiah;
            }
            
            return rupiah;
        }

        // 1. Jika ada old value (misal habis error validasi), format dulu
        if(hiddenInput.value) {
            displayInput.value = formatRupiah(hiddenInput.value);
        }

        // 2. Event saat mengetik
        displayInput.addEventListener('keyup', function(e) {
            // Izinkan angka dan tanda minus
            let val = this.value.replace(/[^\d-]/g, ''); 
            
            // Update tampilan visual
            this.value = formatRupiah(val);
            
            // Update nilai asli ke hidden input (hapus titik, biarkan minus)
            hiddenInput.value = val; 
        });

        // 3. Validasi Submit
        $('#recon-create-form').on('submit', function(e) {
            if (hiddenInput.value === '' || hiddenInput.value === '-') {
                e.preventDefault();
                Swal.fire('Error', 'Saldo Akhir tidak boleh kosong!', 'error');
            }
        });
    });
</script>
@endpush