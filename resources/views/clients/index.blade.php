@extends('layouts.app') {{-- Sesuaikan layout admin Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola Klien</h2>
        <div>
            @if(request('status') === 'deleted')
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Klien Aktif
                </a>
            @else
                <a href="{{ route('clients.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Lihat Arsip Klien
                </a>
                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Klien Baru
                </a>
            @endif
        </div>
    </div>

    {{-- Notifikasi SweetAlert Toast akan menggantikan ini --}}
    {{-- @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif --}}
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Klien</th>
                            <th>Email</th>
                            <th>Penanggung Jawab</th>
                            <th>No. Telepon</th>
                            <th class="text-center">Status Akun</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                        <tr>
                            <td>{{ $loop->iteration + $clients->firstItem() - 1 }}</td>
                            <td>{{ $client->client_name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->person_in_charge ?? '-' }}</td>
                            <td>{{ $client->phone_number ?? '-' }}</td>
                            <td class="text-center">
                                {{-- ✅ LOGIKA STATUS BARU --}}
                                @if($client->trashed())
                                    <span class="badge bg-danger">Diarsipkan</span>
                                @elseif($client->is_locked)
                                    <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i> Dikunci</span>
                                @elseif($client->is_approved)
                                    <span class="badge bg-success">Aktif & Disetujui</span>
                                @else
                                    <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                
                                    @if($client->trashed())
                                        {{-- ✅ TOMBOL RESTORE (DIPERBARUI) --}}
                                        <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-restore d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-info" data-name="{{ $client->client_name }}">Pulihkan</button>
                                        </form>
                                    @else
                                        {{-- JIKA AKTIF: Tampilkan tombol seperti biasa --}}
                                        @if(!$client->is_approved)
                                        {{-- ✅ TOMBOL APPROVE (DIPERBARUI) --}}
                                        <form action="{{ route('clients.approve', $client->client_id) }}" method="POST" class="form-approve d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" data-name="{{ $client->client_name }}">Setujui</button>
                                        </form>
                                        @endif

                                        @if($client->is_locked)
                                            {{-- Tombol Buka Kunci --}}
                                            <form action="{{ route('clients.unlock', $client->client_id) }}" method="POST" class="form-unlock d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-warning" data-name="{{ $client->client_name }}" title="Buka Kunci Akun">
                                                    <i class="bi bi-unlock-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Tombol Kunci --}}
                                            <form action="{{ route('clients.lock', $client->client_id) }}" method="POST" class="form-lock d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-secondary" data-name="{{ $client->client_name }}" title="Kunci Akun">
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                    
                                        <a href="{{ route('clients.edit', $client->client_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        
                                        {{-- ✅ TOMBOL DELETE (DIPERBARUI) --}}
                                        <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-delete d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" data-name="{{ $client->client_name }}"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">Tidak ada data klien.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $clients->appends(request()->query())->links() }}
    </div>
</div>
@endsection


{{-- ✅ TAMBAHKAN SEMUA JAVASCRIPT DI BAWAH INI --}}
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


        // --- 2. KONFIRMASI AKSI "SETUJUI" ---
        document.querySelectorAll('.form-approve').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan submit
                const clientName = this.querySelector('button').dataset.name;
                
                Swal.fire({
                    title: `Setujui Klien Ini?`,
                    text: `Anda yakin ingin menyetujui klien "${clientName}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745', // Hijau
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Lanjutkan submit
                    }
                });
            });
        });

        
        // --- 3. KONFIRMASI AKSI "DELETE" ---
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan submit
                const clientName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan menghapus klien "${clientName}". Klien bisa dipulihkan dari Arsip.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Merah
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Lanjutkan submit
                    }
                });
            });
        });

        
        // --- 4. KONFIRMASI AKSI "PULIHKAN/RESTORE" ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan submit
                const clientName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Pulihkan Klien Ini?',
                    text: `Anda akan memulihkan akun untuk klien "${clientName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8', // Biru-Info
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Lanjutkan submit
                    }
                });
            });
        });

        document.querySelectorAll('.form-lock').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 
                const clientName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Kunci Akun Ini?',
                    text: `Klien "${clientName}" tidak akan bisa login atau mengakses portal.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6c757d', // Abu-abu
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Kunci!',
                    cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        });

        // --- ✅ 6. KONFIRMASI BUKA KUNCI AKUN ---
        document.querySelectorAll('.form-unlock').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 
                const clientName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Buka Kunci Akun Ini?',
                    text: `Klien "${clientName}" akan bisa login dan mengakses portal kembali.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107', // Kuning
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Buka Kunci!',
                    cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        });

    });
</script>
@endpush