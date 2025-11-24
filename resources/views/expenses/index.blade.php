@extends('layouts.app')

@section('title', 'Beban Operasional')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Data Beban Operasional</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar pengeluaran rutin perusahaan.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Pengeluaran
            </a>
        </div>
    </div>

    {{-- FILTER FORM --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('expenses.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-end">
                <div class="md:col-span-4">
                    <label for="search" class="block text-xs font-bold text-gray-500 uppercase mb-1">Pencarian</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="material-icons text-gray-400 text-sm">search</i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Deskripsi atau Kategori...">
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="start_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('start_date') }}">
                </div>

                <div class="md:col-span-2">
                    <label for="end_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('end_date') }}">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition">
                        <i class="material-icons text-sm mr-2">filter_alt</i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">Deskripsi</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Oleh</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $expense->expense_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                    {{ $expense->expenseAccount->account_name ?? $expense->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-1" title="{{ $expense->description }}">
                                    {{ Str::limit($expense->description, 50) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-red-600">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                {{ $expense->user->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="p-1.5 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition border border-gray-200 shadow-sm hover:border-yellow-200" title="Edit">
                                        <i class="material-icons text-lg leading-none">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="form-delete-expense inline-block" data-expense-label="{{ $expense->description }} (Rp {{ number_format($expense->amount, 0, ',', '.') }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Hapus">
                                            <i class="material-icons text-lg leading-none">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">receipt</i>
                                    <p class="text-base font-medium">Belum ada data</p>
                                    <p class="text-sm mt-1">Silakan tambahkan pengeluaran baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Pengeluaran</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-red-700">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        @if($expenses->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $expenses->links() }}
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
    const deleteForms = document.querySelectorAll('.form-delete-expense');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const expenseLabel = event.target.dataset.expenseLabel;
            
            Swal.fire({
                title: 'Hapus Pengeluaran?',
                html: `Anda akan menghapus:<br><b>${expenseLabel}</b><br><br><span class="text-red-500 text-xs">Tindakan ini juga akan membalik jurnal akuntansi terkait.</span>`,
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
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
            timer: 5000,
            showConfirmButton: false
        });
    @endif
});
</script>
@endpush