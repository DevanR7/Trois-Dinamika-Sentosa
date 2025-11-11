@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Akun Bank Perusahaan</h2>
        <a href="{{ route('company-bank-accounts.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Tambah Akun Bank
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Bank</th>
                            <th>Atas Nama</th>
                            <th>No. Rekening</th>
                            {{-- ✅ KOLOM BARU --}}
                            <th>Terhubung ke Akun (COA)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                        <tr>
                            <td class="fw-semibold">{{ $account->bank_name }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td>{{ $account->account_number ?? '-' }}</td>
                            
                            {{-- ✅ DATA BARU --}}
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
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('company-bank-accounts.edit', $account) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                
                                {{-- ✅ MODIFIKASI: Hapus onsubmit, tambah class & data attribute --}}
                                <form action="{{ route('company-bank-accounts.destroy', $account) }}" method="POST" 
                                      class="d-inline form-delete-bank-account" 
                                      data-account-label="{{ $account->bank_name }} ({{ $account->account_name }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            {{-- ✅ Colspan disesuaikan --}}
                            <td colspan="6" class="text-center text-muted">Belum ada data akun bank.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ✅ MODIFIKASI: Tambah script SweetAlert --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-bank-account');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            // Ambil nama akun dari data attribute
            const accountLabel = event.target.dataset.accountLabel;
            const warningText = `Anda yakin ingin menghapus akun bank: "${accountLabel}"? Tindakan ini tidak dapat diurungkan.`;

            Swal.fire({
                title: 'Anda Yakin?',
                text: warningText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika dikonfirmasi, submit form-nya
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush