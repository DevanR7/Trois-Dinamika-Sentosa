@extends('layouts.app')

@section('title', 'Proses Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('batch-payments.pending') }}" class="hover:text-indigo-600 transition-colors">Verifikasi</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Verifikasi Pembayaran</h1>
        </div>
        <a href="{{ route('batch-payments.pending') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg font-bold text-sm text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: INFO --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <i class="material-icons text-[20px]">receipt_long</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Detail Transaksi</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        
                        {{-- Klien --}}
                        <div class="sm:col-span-2 flex items-center gap-4 pb-6 border-b border-slate-100">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                <i class="material-icons text-xl">business</i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Klien</p>
                                <h4 class="text-lg font-bold text-slate-800">{{ $batchPayment->client->client_name ?? 'N/A' }}</h4>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Metode Pembayaran</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ $batchPayment->paymentMethod->name ?? 'N/A' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tanggal Lapor</label>
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <i class="material-icons text-slate-400 text-[16px]">event</i>
                                {{ $batchPayment->created_at->format('d F Y, H:i') }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Diterima Oleh</label>
                            <span class="text-sm font-medium text-slate-800">
                                {{ str_contains(strtolower($batchPayment->paymentMethod->name ?? ''), 'cash') ? ($salesUser->full_name ?? '-') : '-' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bukti Transfer</label>
                            @if(!empty($details['proof_path']))
                                <a href="{{ asset('storage/' . $details['proof_path']) }}" target="_blank" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline gap-1">
                                    <i class="material-icons text-[18px]">image</i> Lihat Bukti
                                </a>
                            @else
                                <span class="text-slate-400 text-sm italic">Tidak ada bukti.</span>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Catatan</label>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 italic min-h-[60px]">
                                "{{ $details['notes'] ?? 'Tidak ada catatan.' }}"
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: AKSI --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- Card Nominal --}}
            <div class="dashboard-card p-6 text-center shadow-md bg-gradient-to-br from-white to-slate-50">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Nominal</p>
                <h2 class="text-3xl font-bold text-emerald-600 font-mono tracking-tight">Rp {{ number_format($batchPayment->total_amount, 0, ',', '.') }}</h2>
            </div>

            {{-- Card Aksi --}}
            <div class="dashboard-card p-6 shadow-lg border-t-4 border-indigo-500">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="material-icons text-indigo-600">verified_user</i> Aksi Verifikasi
                </h3>

                {{-- FORM APPROVE --}}
                <form action="{{ route('batch-payments.approve', $batchPayment->batch_payment_id) }}" method="POST" class="form-confirm space-y-4">
                    @csrf
                    <div>
                        <label for="company_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Setor Ke Akun</label>
                        <select name="company_bank_account_id" id="company_bank_account_id" class="form-input select2-basic" required>
                            <option value="">-- Pilih Akun --</option>
                            @foreach($companyBankAccounts as $account)
                                <option value="{{ $account->company_bank_account_id }}">
                                    {{ $account->bank_name }} - {{ $account->account_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" 
                            data-title="Setujui Pembayaran?" 
                            data-text="Dana akan dialokasikan ke invoice terkait." 
                            data-btn-text="Ya, Setujui" 
                            data-btn-color="#059669"
                            data-icon="question"
                            class="w-full h-[48px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold shadow-md transition flex justify-center items-center gap-2 hover:-translate-y-0.5">
                        <i class="material-icons text-[18px]">check_circle</i> Setujui & Alokasikan
                    </button>
                </form>

                <hr class="border-dashed border-slate-200 my-4">

                {{-- FORM REJECT --}}
                <form action="{{ route('batch-payments.reject', $batchPayment->batch_payment_id) }}" method="POST" class="form-confirm">
                    @csrf
                    <button type="submit" 
                            data-title="Tolak Pembayaran?" 
                            data-text="Status akan menjadi Rejected. Saldo kredit (jika dipakai) akan dikembalikan." 
                            data-btn-text="Ya, Tolak" 
                            data-btn-color="#ef4444"
                            data-icon="warning"
                            class="w-full h-[48px] bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition flex justify-center items-center gap-2 shadow-sm">
                        <i class="material-icons text-[18px]">cancel</i> Tolak Pembayaran
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-basic').select2({ placeholder: '-- Pilih Akun --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush