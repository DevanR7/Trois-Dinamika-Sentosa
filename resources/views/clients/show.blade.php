@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Judul Halaman dan Tombol Kembali --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            Detail Klien: 
            <span class="fw-bold">{{ $client->client_name }}</span>
        </h2>
        <a href="{{ route('clients.index') }}" class="btn btn-secondary d-flex align-items-center">
            <span class="material-icons me-1">arrow_back</span>
            Kembali
        </a>
    </div>

    <div class="row">
        {{-- Kolom Kiri: Informasi Detail Klien --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Klien</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Nama Klien</dt>
                        <dd class="col-sm-8">{{ $client->client_name }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $client->email ?? '-' }}</dd>

                        <dt class="col-sm-4">Telepon</dt>
                        <dd class="col-sm-8">{{ $client->phone_number ?? '-' }}</dd>

                        <dt class="col-sm-4">Person in Charge (PIC)</dt>
                        <dd class="col-sm-8">{{ $client->person_in_charge ?? '-' }}</dd>

                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $client->address ?? '-' }}</dd>

                        <dt class="col-sm-4">Tanggal Registrasi</dt>
                        <dd class="col-sm-8">{{ $client->created_at->isoFormat('dddd, D MMMM YYYY') }}</dd>
                        
                        {{-- =============================================== --}}
                        {{-- ✅ PERBAIKAN DI SINI: Gunakan Accessor 'balance' --}}
                        {{-- =============================================== --}}
                        <dt class="col-sm-4 pt-2 border-top mt-2">Saldo Kredit</dt>
                        <dd class="col-sm-8 pt-2 border-top mt-2">
                            <span class="fw-bold {{ $client->balance > 0 ? 'text-success' : '' }}">
                                Rp {{ number_format($client->balance ?? 0, 0, ',', '.') }}
                            </span>
                        </dd>
                        {{-- =============================================== --}}
                        
                    </dl>
                </div>
            </div>

            {{-- Card untuk Riwayat Invoice --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Invoice (5 Terbaru)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Ambil 5 invoice terbaru dari relasi --}}
                                @forelse($client->salesInvoices()->latest('order_date')->take(5)->get() as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->order_date->format('d M Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($invoice->status == 'paid')
                                                <span class="badge bg-success">Lunas</span>
                                            @elseif($invoice->status == 'partially_paid')
                                                <span class="badge bg-warning text-dark">Sebagian</span>
                                            @elseif($invoice->status == 'unpaid')
                                                <span class="badge bg-danger">Belum Lunas</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $invoice->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-info" title="Lihat Invoice">
                                                <span class="material-icons" style="font-size: 1rem;">visibility</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Belum ada riwayat invoice untuk klien ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($client->salesInvoices()->count() > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('invoices.index', ['search' => $client->client_name]) }}">Lihat Semua Invoice Klien Ini</a>
                </div>
                @endif
            </div>

            {{-- =============================================== --}}
            {{-- ✅ TAMBAHAN BARU: Card untuk Riwayat Saldo (Ledger) --}}
            {{-- =============================================== --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Saldo Kredit (Ledger)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Kredit (Masuk)</th>
                                    <th class="text-end">Debit (Keluar)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgers as $ledger)
                                    <tr>
                                        <td>{{ $ledger->transaction_date->format('d M Y') }}</td>
                                        <td>
                                            {{ $ledger->description }}
                                            {{-- Link ke referensi jika ada --}}
                                            @if($ledger->reference_type === \App\Models\SalesReturn::class && $ledger->reference)
                                                <a href="{{ route('sales-returns.show', $ledger->reference_id) }}" class="d-block small" target="_blank">Lihat Retur</a>
                                            @elseif($ledger->reference_type === \App\Models\Payment::class && $ledger->reference)
                                                <a href="{{ route('invoices.show', $ledger->reference->salesInvoice->invoice_id) }}" class="d-block small" target="_blank">Lihat Invoice #{{ $ledger->reference->salesInvoice->invoice_number }}</a>
                                            @elseif($ledger->reference_type === \App\Models\BatchPayment::class && $ledger->reference)
                                                <span class="d-block small text-muted">Ref: Pembayaran Batch #{{ $ledger->reference_id }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-success">
                                            @if($ledger->type == 'credit' && $ledger->amount > 0)
                                                Rp {{ number_format($ledger->amount, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end text-danger">
                                            @if($ledger->type == 'debit' && $ledger->amount < 0)
                                                Rp {{ number_format(abs($ledger->amount), 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">Belum ada riwayat transaksi saldo kredit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($ledgers->hasPages())
                <div class="card-footer">
                    {{-- Tampilkan link paginasi (menggunakan nama 'ledger_page' kustom) --}}
                    {{ $ledgers->links() }}
                </div>
                @endif
            </div>

        </div> {{-- Tutup col-lg-8 --}}

        {{-- Kolom Kanan: Status dan Tombol Tindakan --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status & Tindakan</h5>
                </div>
                <div class="card-body">
                    {{-- Bagian Status --}}
                    <h6 class="mb-3">Status Akun</h6>
                    <div class="mb-3">
                        @if($client->trashed())
                            <span class="badge bg-danger p-2 w-100 fs-6">
                                <span class="material-icons me-1" style="font-size: 1.1rem; vertical-align: middle;">archive</span>
                                DIARSIPKAN
                            </span>
                        @else
                            <p class="mb-2">
                                @if($client->is_approved)
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                                @endif
                            </p>
                            <p class="mb-0">
                                @if($client->is_locked)
                                    <span class="badge bg-dark">Terkunci</span>
                                @else
                                    <span class="badge bg-info text-dark">Aktif</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    
                    <hr>

                    {{-- Bagian Tindakan (Tombol) --}}
                    <h6 class="mb-3">Tindakan</h6>
                    <div class="d-grid gap-2">
                        
                        {{-- Jika Klien diarsipkan --}}
                        @if($client->trashed())
                            @can('delete-clients')
                            {{-- ✅ Form Pulihkan --}}
                            <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-restore">
                                @csrf
                                @method('PATCH')
                                <button type="submit" data-name="{{ $client->client_name }}" class="btn btn-success w-100 d-flex align-items-center justify-content-center">
                                    <span class="material-icons me-1">restore_from_trash</span> Pulihkan
                                </button>
                            </form>
                            @endcan
                        
                        {{-- Jika Klien Aktif --}}
                        @else
                            @can('edit-clients')
                            <a href="{{ route('clients.edit', $client->client_id) }}" class="btn btn-primary d-flex align-items-center justify-content-center">
                                <span class="material-icons me-1">edit</span> Edit Klien
                            </a>
                            @endcan

                            @if(!$client->is_approved)
                                @can('edit-clients')
                                {{-- ✅ Form Setujui --}}
                                <form action="{{ route('clients.approve', $client->client_id) }}" method="POST" class="form-approve">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" data-name="{{ $client->client_name }}" class="btn btn-success w-100 d-flex align-items-center justify-content-center">
                                        <span class="material-icons me-1">check_circle_outline</span> Setujui Akun
                                    </button>
                                </form>
                                @endcan
                            @endif

                            @can('edit-clients')
                                @if($client->is_locked)
                                {{-- ✅ Form Buka Kunci --}}
                                <form action="{{ route('clients.unlock', $client->client_id) }}" method="POST" class="form-unlock">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" data-name="{{ $client->client_name }}" class="btn btn-info text-dark w-100 d-flex align-items-center justify-content-center">
                                        <span class="material-icons me-1">lock_open</span> Buka Kunci
                                    </button>
                                </form>
                                @else
                                {{-- ✅ Form Kunci --}}
                                <form action="{{ route('clients.lock', $client->client_id) }}" method="POST" class="form-lock">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" data-name="{{ $client->client_name }}" class="btn btn-dark w-100 d-flex align-items-center justify-content-center">
                                        <span class="material-icons me-1">lock</span> Kunci Akun
                                    </button>
                                </form>
                                @endif
                            @endcan

                            @can('delete-clients')
                            {{-- ✅ Form Arsipkan --}}
                            <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-name="{{ $client->client_name }}" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
                                    <span class="material-icons me-1">archive</span> Arsipkan
                                </button>
                            </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- ✅ TAMBAHKAN SEMUA SCRIPT SWEETALERT DI BAWAH INI --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. NOTIFIKASI TOAST (SETELAH AKSI) ---
        // Cek jika ada session 'success'
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end', // Posisi di pojok kanan atas
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000, // 3 detik
                timerProgressBar: true
            });
        @endif

        // Cek jika ada session 'error'
        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 5000, // 5 detik
                timerProgressBar: true
            });
        @endif

        // --- 2. KONFIRMASI "SETUJUI" ---
        const approveForm = document.querySelector('.form-approve');
        if (approveForm) {
            approveForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const clientName = this.querySelector('button').dataset.name;
                
                Swal.fire({
                    title: `Setujui Klien Ini?`,
                    text: `Anda yakin ingin menyetujui klien "${clientName}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // --- 3. KONFIRMASI "ARSIPKAN" (DELETE) ---
        const deleteForm = document.querySelector('.form-delete');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const clientName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan mengarsipkan klien "${clientName}". Klien yang diarsipkan tidak bisa login.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // --- 4. KONFIRMASI "PULIHKAN" (RESTORE) ---
        const restoreForm = document.querySelector('.form-restore');
        if (restoreForm) {
            restoreForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const clientName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Pulihkan Klien Ini?',
                    text: `Anda akan memulihkan akun untuk klien "${clientName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // --- 5. KONFIRMASI "KUNCI" ---
        const lockForm = document.querySelector('.form-lock');
        if (lockForm) {
            lockForm.addEventListener('submit', function(e) {
                e.preventDefault(); 
                const clientName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Kunci Akun Ini?',
                    text: `Klien "${clientName}" tidak akan bisa login atau mengakses portal.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6c757d',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Kunci!',
                    cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        }

        // --- 6. KONFIRMASI "BUKA KUNCI" ---
        const unlockForm = document.querySelector('.form-unlock');
        if (unlockForm) {
            unlockForm.addEventListener('submit', function(e) {
                e.preventDefault(); 
                const clientName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Buka Kunci Akun Ini?',
                    text: `Klien "${clientName}" akan bisa login dan mengakses portal kembali.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Buka Kunci!',
                    cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        }

    });
</script>
@endpush