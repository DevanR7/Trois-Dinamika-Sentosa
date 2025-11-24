@extends('layouts.app')

@section('title', 'Data Pinjaman')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Data Pinjaman</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar pinjaman perusahaan (Hutang Bank/Koperasi).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('loans.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Pinjaman
            </a>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl. Pinjam</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pemberi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Akun Utang</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Pokok Awal</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Pokok</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($loans as $loan)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $loan->loan_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                    {{ $loan->lender_name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded border border-gray-200">
                                    {{ $loan->loanAccount->account_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-500">
                                Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono font-bold text-red-600">
                                Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($loan->status == 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                        Belum Lunas
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        Lunas
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('loans.show', $loan) }}" class="p-1.5 bg-white text-indigo-600 rounded-lg hover:bg-indigo-50 transition border border-gray-200 shadow-sm hover:border-indigo-200" title="Lihat & Bayar">
                                        <i class="material-icons text-lg leading-none">visibility</i>
                                    </a>
                                    
                                    @if (!$loan->payments()->exists())
                                        <a href="{{ route('loans.edit', $loan) }}" class="p-1.5 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition border border-gray-200 shadow-sm hover:border-yellow-200" title="Edit">
                                            <i class="material-icons text-lg leading-none">edit</i>
                                        </a>
                                        
                                        <form action="{{ route('loans.destroy', $loan) }}" method="POST" 
                                              class="d-inline form-delete-loan" 
                                              data-loan-label="{{ $loan->lender_name }} (Rp {{ number_format($loan->principal_amount, 0, ',', '.') }})">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Hapus">
                                                <i class="material-icons text-lg leading-none">delete</i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">account_balance_wallet</i>
                                    <p class="text-base font-medium">Belum ada data</p>
                                    <p class="text-sm mt-1">Silakan tambahkan pinjaman baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Konfirmasi Delete
    const deleteForms = document.querySelectorAll('.form-delete-loan');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const loanLabel = event.target.dataset.loanLabel;
            
            Swal.fire({
                title: 'Hapus Pinjaman?',
                html: `Anda akan menghapus:<br><b>${loanLabel}</b><br><br><span class="text-red-600 font-bold text-xs">PERINGATAN: Jurnal penerimaan pinjaman akan dibalik/dihapus.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
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
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" });
    @endif
});
</script>
@endpush