@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Daftar Akun (Chart of Accounts)</h2>
        <a href="{{ route('chart-of-accounts.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Tambah Akun Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 15%;">No. Akun</th>
                            <th style="width: 35%;">Nama Akun</th>
                            <th style="width: 15%;">Tipe Akun</th>
                            <th style="width: 10%;">Saldo Normal</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parentAccounts as $parent)
                            {{-- Baris Akun Induk --}}
                            <tr class="table-light fw-bold">
                                <td>{{ $parent->account_number }}</td>
                                <td>{{ $parent->account_name }}</td>
                                <td>{{ $parent->account_type }}</td>
                                <td>{{ $parent->normal_balance }}</td>
                                <td>
                                    @if ($parent->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Non-Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('chart-of-accounts.edit', $parent) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    
                                    {{-- ✅ MODIFIKASI: Hapus onsubmit, tambah class & data attribute --}}
                                    <form action="{{ route('chart-of-accounts.destroy', $parent) }}" method="POST" 
                                          class="d-inline form-delete-account" 
                                          data-account-name="{{ $parent->account_name }}" 
                                          data-is-parent="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            {{-- Baris Akun Anak --}}
                            @foreach ($parent->children as $child)
                            <tr>
                                <td class="ps-4"><i class="bi bi-arrow-return-right me-2"></i>{{ $child->account_number }}</td>
                                <td class="ps-4">{{ $child->account_name }}</td>
                                <td>{{ $child->account_type }}</td>
                                <td>{{ $child->normal_balance }}</td>
                                <td>
                                    @if ($child->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Non-Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('chart-of-accounts.edit', $child) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    {{-- ✅ MODIFIKASI: Hapus onsubmit, tambah class & data attribute --}}
                                    <form action="{{ route('chart-of-accounts.destroy', $child) }}" method="POST" 
                                          class="d-inline form-delete-account" 
                                          data-account-name="{{ $child->account_name }}" 
                                          data-is-parent="false">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data akun. Silakan buat yang baru.</td>
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
    const deleteForms = document.querySelectorAll('.form-delete-account');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            const accountName = event.target.dataset.accountName;
            const isParent = event.target.dataset.isParent === 'true';
            
            let warningText = `Anda yakin ingin menghapus akun "${accountName}"?`;
            if (isParent) {
                // Beri peringatan khusus jika ini akun induk
                warningText = `Anda akan menghapus AKUN INDUK "${accountName}". Ini MUNGKIN akan menghapus semua akun anaknya.`;
            }
            warningText += " Tindakan ini tidak dapat diurungkan.";

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