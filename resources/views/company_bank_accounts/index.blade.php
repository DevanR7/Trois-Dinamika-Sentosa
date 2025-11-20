@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Akun Bank Perusahaan</h2>
        <a href="{{ route('company-bank-accounts.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Tambah Akun Bank
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Nama Bank</th>
                            <th class="py-3">Atas Nama</th>
                            <th class="py-3">No. Rekening</th>
                            <th class="py-3">Terhubung ke Akun (COA)</th>
                            <th class="py-3">Status</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $account->bank_name }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td class="font-monospace">{{ $account->account_number ?? '-' }}</td>
                            
                            <td>
                                @if($account->account)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                        {{ $account->account->account_number }} - {{ $account->account->account_name }}
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Belum Terhubung
                                    </span>
                                @endif
                            </td>
                            
                            <td>
                                @if ($account->is_active)
                                    <span class="badge bg-success rounded-pill px-3">Aktif</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill px-3">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('company-bank-accounts.edit', $account) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <form action="{{ route('company-bank-accounts.destroy', $account) }}" method="POST" 
                                          class="d-inline form-delete-bank-account" 
                                          data-account-label="{{ $account->bank_name }} ({{ $account->account_name }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data akun bank.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-bank-account');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            const accountLabel = event.target.dataset.accountLabel;
            const warningText = `Anda yakin ingin menghapus akun bank: "${accountLabel}"? Tindakan ini tidak dapat diurungkan.`;

            Swal.fire({
                title: 'Anda Yakin?',
                text: warningText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // Notifikasi
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", });
    @endif
});
</script>
@endpush