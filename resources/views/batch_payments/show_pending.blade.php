@extends('layouts.app')

@section('title', 'Proses Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('batch-payments.pending') }}" class="hover:text-indigo-600 transition">Verifikasi</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Proses Pembayaran</h2>
            <p class="text-sm text-gray-500 mt-1">Klien: <span class="font-bold text-indigo-600">{{ $batchPayment->client->client_name ?? 'N/A' }}</span></p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('batch-payments.pending') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: DETAIL --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <i class="material-icons text-gray-400">info</i>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Detail Transaksi</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        
                        {{-- Metode --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Metode Pembayaran</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-50 text-indigo-700">
                                {{ $batchPayment->paymentMethod->name ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Lapor</label>
                            <span class="text-gray-900 font-medium flex items-center gap-2">
                                <i class="material-icons text-sm text-gray-400">event</i>
                                {{ $batchPayment->created_at->format('d F Y, H:i') }}
                            </span>
                        </div>

                        {{-- Sales --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Diterima Oleh</label>
                            @if($batchPayment->paymentMethod && str_contains(strtolower($batchPayment->paymentMethod->name), 'cash'))
                                <span class="text-gray-900 font-medium">{{ $salesUser->full_name ?? 'N/A' }}</span>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </div>

                        {{-- Bukti Transfer --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bukti Transfer</label>
                            @if(!empty($details['proof_path']))
                                <a href="{{ asset('storage/' . $details['proof_path']) }}" target="_blank" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                    <i class="material-icons text-lg mr-1">image</i> Lihat Bukti
                                </a>
                            @else
                                <span class="text-gray-400 italic text-sm">Tidak ada bukti terlampir.</span>
                            @endif
                        </div>

                        {{-- Catatan --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan Klien</label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-sm text-gray-600 italic">
                                "{{ $details['notes'] ?? 'Tidak ada catatan.' }}"
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RINGKASAN & AKSI (Sticky) --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <i class="material-icons text-gray-400">calculate</i>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Ringkasan Alokasi</h3>
                </div>

                <div class="p-6">
                    {{-- PERHITUNGAN PHP --}}
                    @php
                        $totalTagihan = 0;
                        foreach($invoices as $inv) { $totalTagihan += $inv->remaining_balance; }
                        
                        $kreditDipakai = (float)($details['credit_amount_to_use'] ?? 0);
                        $inputDana = (float)$batchPayment->total_amount;
                        $totalDana = $kreditDipakai + $inputDana;
                        $overpayment = max(0, $totalDana - $totalTagihan);
                    @endphp

                    {{-- Summary Rows --}}
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total Tagihan ({{ count($invoices) }})</span>
                            <span class="font-medium text-gray-900">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
                        </div>
                        
                        <hr class="border-dashed border-gray-300">
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Dana Transfer/Cash</span>
                            <span class="font-bold text-indigo-600">(+) Rp {{ number_format($inputDana, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Saldo Kredit Dipakai</span>
                            <span class="font-bold text-green-600">(+) Rp {{ number_format($kreditDipakai, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex justify-between items-center mb-4">
                        <span class="text-sm font-bold text-gray-800">TOTAL DANA</span>
                        <span class="text-xl font-bold text-green-600">Rp {{ number_format($totalDana, 0, ',', '.') }}</span>
                    </div>

                    {{-- Overpayment Info --}}
                    @if($overpayment > 0)
                        <div class="mb-6 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-start gap-3">
                            <i class="material-icons text-blue-500 text-lg mt-0.5">info</i>
                            <div class="text-xs text-blue-800">
                                <span class="font-bold block mb-1">Overpayment:</span>
                                Rp {{ number_format($overpayment, 0, ',', '.') }} akan otomatis masuk ke <strong>Saldo Kredit</strong> klien.
                            </div>
                        </div>
                    @endif

                    {{-- FORM APPROVE --}}
                    <form action="{{ route('batch-payments.approve', $batchPayment->batch_payment_id) }}" method="POST" id="form-approve" class="space-y-4">
                        @csrf
                        <div>
                            <label for="company_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Setor Ke Akun</label>
                            <select name="company_bank_account_id" id="company_bank_account_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                <option value="">-- Pilih Akun --</option>
                                @foreach($companyBankAccounts as $account)
                                    <option value="{{ $account->company_bank_account_id }}">
                                        {{ $account->bank_name }} - {{ $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                            <i class="material-icons text-lg mr-2">check_circle</i> Setujui & Alokasikan
                        </button>
                    </form>

                    {{-- FORM REJECT --}}
                    <form action="{{ route('batch-payments.reject', $batchPayment->batch_payment_id) }}" method="POST" id="form-reject" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-red-300 rounded-lg shadow-sm text-sm font-bold text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                            <i class="material-icons text-lg mr-2">cancel</i> Tolak Pembayaran
                        </button>
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Konfirmasi Approve
    const approveForm = document.getElementById('form-approve');
    if (approveForm) {
        approveForm.addEventListener('submit', function (e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Setujui Pembayaran?',
                text: "Dana akan dialokasikan ke invoice dan saldo diperbarui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a', // green-600
                cancelButtonColor: '#6b7280',  // gray-500
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) this.submit(); });
        });
    }

    // Konfirmasi Reject
    const rejectForm = document.getElementById('form-reject');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Tolak Pembayaran?',
                text: "Status pembayaran akan diubah menjadi Rejected.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626', // red-600
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) this.submit(); });
        });
    }
});
</script>
@endpush