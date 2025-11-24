@extends('layouts.app')

@section('title', 'Detail Pinjaman')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('loans.index') }}" class="hover:text-indigo-600 transition">Pinjaman</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Detail Pinjaman</h2>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('loans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- INFO UTAMA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="p-6 flex flex-col md:flex-row gap-8 items-center">
            
            <div class="flex-1 w-full">
                <h3 class="text-2xl font-bold text-indigo-600 mb-1">{{ $loan->lender_name }}</h3>
                <p class="text-gray-500 mb-4 text-sm">{{ $loan->description ?? 'Tidak ada deskripsi' }}</p>
                
                <div class="flex gap-8">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tanggal Pinjam</p>
                        <p class="text-lg font-medium text-gray-900">{{ $loan->loan_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status</p>
                        @if ($loan->status == 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1 border border-yellow-200">
                                Belum Lunas
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1 border border-green-200">
                                Lunas
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 bg-gray-50 p-5 rounded-xl border border-gray-200">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sisa Pokok Pinjaman</p>
                <h2 class="text-3xl font-bold text-red-600 mb-2">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h2>
                
                @php
                    $percentPaid = ($loan->principal_amount > 0) 
                        ? (($loan->principal_amount - $loan->remaining_balance) / $loan->principal_amount) * 100 
                        : 0;
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                    <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percentPaid }}%"></div>
                </div>
                <p class="text-xs text-gray-500 text-right">Terbayar: {{ round($percentPaid) }}%</p>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-100 p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Pinjaman Awal</p>
                <p class="font-bold text-gray-900">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Pokok Dibayar</p>
                <p class="font-bold text-green-600">Rp {{ number_format($loan->payments->sum('principal_paid'), 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Bunga Dibayar</p>
                <p class="font-bold text-yellow-600">Rp {{ number_format($loan->payments->sum('interest_paid'), 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Akun Akuntansi</p>
                <p class="text-xs text-gray-700 font-medium truncate" title="{{ $loan->loanAccount->account_name }}">{{ $loan->loanAccount->account_name }} (Utang)</p>
                <p class="text-xs text-gray-700 font-medium truncate" title="{{ $loan->cashBankAccount->account_name }}">{{ $loan->cashBankAccount->account_name }} (Kas)</p>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h5 class="font-bold text-gray-800 text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="material-icons text-gray-400">history</i> Riwayat Cicilan
            </h5>
            
            @if($loan->status == 'active')
                <a href="{{ route('loans.payments.create', $loan) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                    <i class="material-icons text-sm mr-1">add</i> Bayar Cicilan
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Pokok</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Bunga</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total Bayar</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Via Akun</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($loan->payments as $payment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->payment_date->format('d/m/y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 truncate max-w-xs">{{ $payment->notes ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-gray-900">Rp {{ number_format($payment->principal_paid, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-gray-500">Rp {{ number_format($payment->interest_paid, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono font-bold text-green-600">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">{{ $payment->cashBankAccount->account_name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <form action="{{ route('loans.payments.destroy', [$loan, $payment]) }}" method="POST" 
                                      class="d-inline form-delete-payment"
                                      data-payment-label="Pembayaran tgl {{ $payment->payment_date->format('d/m/Y') }} sebesar Rp {{ number_format($payment->total_paid, 0, ',', '.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-red-400 hover:text-red-600 transition rounded-full hover:bg-red-50" title="Batalkan">
                                        <i class="material-icons text-lg">cancel</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 text-sm">Belum ada riwayat pembayaran cicilan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteForms = document.querySelectorAll('.form-delete-payment');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); 
                const label = event.target.dataset.paymentLabel;
                
                Swal.fire({
                    title: 'Batalkan Pembayaran?',
                    html: `Anda akan membatalkan:<br><b>${label}</b><br><br><span class="text-red-600 font-bold text-xs">Sisa pokok pinjaman akan dikembalikan dan jurnal dibalik.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) event.target.submit();
                });
            });
        });

        @if(session('success')) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false }); @endif
        @if(session('error')) Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" }); @endif
    });
</script>
@endpush