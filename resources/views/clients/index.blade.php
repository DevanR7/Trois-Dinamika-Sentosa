@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Kelola Klien</h3>
            <p class="text-muted small mb-0">Manajemen data pelanggan dan mitra</p>
        </div>
        
        <div class="d-flex gap-2">
            @if(request('status') === 'deleted')
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Klien Aktif
                </a>
            @else
                <a href="{{ route('clients.index', ['status' => 'deleted']) }}" class="btn btn-light border text-muted shadow-sm">
                    <i class="bi bi-archive me-1"></i> Arsip
                </a>
                <a href="{{ route('clients.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Klien Baru
                </a>
            @endif
        </div>
    </div>

    {{-- CARD LIST --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Klien</th>
                            <th>Kontak</th>
                            <th>PIC</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Saldo Kredit</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $client->client_name }}</div>
                                <small class="text-muted">{{ $client->email ?? '-' }}</small>
                            </td>
                            <td>{{ $client->phone_number ?? '-' }}</td>
                            <td>
                                @if($client->person_in_charge)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle me-2 d-flex justify-content-center align-items-center text-muted" style="width:25px; height:25px;">
                                            <i class="bi bi-person-fill" style="font-size: 0.8rem;"></i>
                                        </div>
                                        {{ $client->person_in_charge }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($client->trashed())
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">Diarsipkan</span>
                                @elseif($client->is_locked)
                                    <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill px-3"><i class="bi bi-lock-fill me-1"></i> Terkunci</span>
                                @elseif($client->is_approved)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">Aktif</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">Menunggu Persetujuan</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($client->balance > 0)
                                    <span class="text-success fw-bold">Rp {{ number_format($client->balance, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($client->trashed())
                                        {{-- Lihat Detail (Deleted) --}}
                                        <a href="{{ route('clients.show', $client->client_id) }}" class="btn btn-sm btn-light border text-primary shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Lihat"><i class="bi bi-eye"></i></a>
                                        
                                        {{-- Pulihkan --}}
                                        <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-restore d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light border text-success shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" data-name="{{ $client->client_name }}" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        </form>
                                    @else
                                        {{-- Approve Button --}}
                                        @if(!$client->is_approved)
                                            <form action="{{ route('clients.approve', $client->client_id) }}" method="POST" class="form-approve d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" data-name="{{ $client->client_name }}" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                        @endif

                                        {{-- Lock/Unlock --}}
                                        @if($client->is_locked)
                                            <form action="{{ route('clients.unlock', $client->client_id) }}" method="POST" class="form-unlock d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-warning shadow-sm text-dark" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" data-name="{{ $client->client_name }}" title="Buka Kunci"><i class="bi bi-unlock-fill"></i></button>
                                            </form>
                                        @else
                                            <form action="{{ route('clients.lock', $client->client_id) }}" method="POST" class="form-lock d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-light border text-secondary shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" data-name="{{ $client->client_name }}" title="Kunci Akun"><i class="bi bi-lock-fill"></i></button>
                                            </form>
                                        @endif

                                        {{-- Standard Actions --}}
                                        <a href="{{ route('clients.show', $client->client_id) }}" class="btn btn-sm btn-light border text-primary shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Lihat"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('clients.edit', $client->client_id) }}" class="btn btn-sm btn-light border text-warning shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        
                                        <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-delete d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" data-name="{{ $client->client_name }}" title="Arsipkan"><i class="bi bi-archive"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada data klien ditemukan.
                            </td>
                        </tr>
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

{{-- SCRIPT JS UNTUK ALERT TETAP SAMA --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toast Notifications
        @if(session('success'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000, timerProgressBar: true });
        @endif
        @if(session('error'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ session('error') }}', showConfirmButton: false, timer: 5000, timerProgressBar: true });
        @endif

        // Confirm Actions Functions
        function confirmAction(formClass, title, text, confirmBtnText, confirmBtnColor) {
            document.querySelectorAll(formClass).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const name = this.querySelector('button').dataset.name;
                    Swal.fire({
                        title: title,
                        text: text.replace(':name', name),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: confirmBtnColor,
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: confirmBtnText,
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) this.submit();
                    });
                });
            });
        }

        confirmAction('.form-approve', 'Setujui Klien?', 'Anda yakin ingin menyetujui klien ":name"?', 'Ya, Setujui!', '#28a745');
        confirmAction('.form-delete', 'Arsipkan Klien?', 'Klien ":name" akan diarsipkan dan tidak bisa login.', 'Ya, Arsipkan!', '#d33');
        confirmAction('.form-restore', 'Pulihkan Klien?', 'Klien ":name" akan dipulihkan kembali.', 'Ya, Pulihkan!', '#17a2b8');
        confirmAction('.form-lock', 'Kunci Akun?', 'Klien ":name" tidak akan bisa login.', 'Ya, Kunci!', '#343a40');
        confirmAction('.form-unlock', 'Buka Kunci?', 'Akses login klien ":name" akan dibuka.', 'Ya, Buka!', '#ffc107');
    });
</script>
@endpush