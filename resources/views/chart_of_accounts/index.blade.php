@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-dark">Daftar Akun (Chart of Accounts)</h2>
        <a href="{{ route('chart-of-accounts.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Tambah Akun Baru
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3" style="width: 15%;">No. Akun</th>
                            <th class="py-3" style="width: 35%;">Nama Akun</th>
                            <th class="py-3" style="width: 15%;">Tipe Akun</th>
                            <th class="py-3" style="width: 10%;">Saldo Normal</th>
                            <th class="py-3" style="width: 10%;">Status</th>
                            <th class="text-center py-3" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parentAccounts as $parent)
                            {{-- Baris Akun Induk --}}
                            <tr class="table-secondary fw-bold">
                                <td class="ps-4">{{ $parent->account_number }}</td>
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
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('chart-of-accounts.edit', $parent) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        
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
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Baris Akun Anak --}}
                            @foreach ($parent->children as $child)
                            <tr>
                                <td class="ps-5"><i class="bi bi-arrow-return-right me-2 text-muted"></i>{{ $child->account_number }}</td>
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
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('chart-of-accounts.edit', $child) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

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
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data akun. Silakan buat yang baru.</td>
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
    
    const deleteForms = document.querySelectorAll('.form-delete-account');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            const accountName = event.target.dataset.accountName;
            const isParent = event.target.dataset.isParent === 'true';
            
            let warningHtml = `Anda yakin ingin menghapus akun <b>"${accountName}"</b>?`;
            if (isParent) {
                warningHtml = `Anda akan menghapus <b>AKUN INDUK "${accountName}"</b>.<br><br><small class="text-danger fw-bold">PERHATIAN: Ini MUNGKIN akan menghapus semua akun anaknya!</small>`;
            }
            warningHtml += "<br><br>Tindakan ini tidak dapat diurungkan.";

            Swal.fire({
                title: 'Hapus Akun?',
                html: warningHtml,
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
    @if(session('success')) Swal.fire('Berhasil!', "{{ session('success') }}", 'success'); @endif
    @if(session('error')) Swal.fire('Gagal!', "{{ session('error') }}", 'error'); @endif
});
</script>
@endpush