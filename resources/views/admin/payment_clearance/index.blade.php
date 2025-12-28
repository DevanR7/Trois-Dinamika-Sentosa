@extends('admin.layouts.app')

@section('title', 'Kliring & Verifikasi Pembayaran')

@section('content')

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Kliring & Verifikasi Pembayaran</h1>
            <p class="page-subtitle">Kelola pembayaran pending (Giro, Cek) dan verifikasi bukti transfer manual.</p>
        </div>
    </div>

    {{-- TABS NAVIGASI --}}
    <div class="flex space-x-1 rounded-xl bg-slate-100/50 p-1 dark:bg-slate-800/50 mb-6 w-fit">
        {{-- Tab Pending --}}
        <a href="{{ route('admin.payment-clearance.index', ['view' => 'pending']) }}"
           class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2 {{ $viewMode === 'pending' ? 'bg-white text-indigo-700 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:bg-white/[0.12] hover:text-indigo-600 dark:text-slate-400' }}">
            <i class="material-icons text-sm">hourglass_empty</i>
            Menunggu Konfirmasi
            @if(isset($pendingPayments) && $pendingPayments->count() > 0 && $viewMode === 'pending')
                <span class="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-600 dark:bg-indigo-900 dark:text-indigo-200">
                    {{ $pendingPayments->count() }}
                </span>
            @endif
        </a>

        {{-- Tab History --}}
        <a href="{{ route('admin.payment-clearance.index', ['view' => 'history']) }}"
           class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2 {{ $viewMode === 'history' ? 'bg-white text-indigo-700 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:bg-white/[0.12] hover:text-indigo-600 dark:text-slate-400' }}">
            <i class="material-icons text-sm">history</i>
            Riwayat Audit
        </a>
    </div>

    {{-- INFO ALERT --}}
    @if($viewMode === 'pending')
        <div class="mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-start gap-3 text-indigo-900 dark:bg-indigo-900/20 dark:border-indigo-800 dark:text-indigo-300">
            <i class="material-icons text-indigo-500 mt-0.5">info</i>
            <div class="text-sm leading-relaxed">
                <span class="font-bold">Panduan Admin:</span>
                <ul class="list-disc ml-5 mt-1 text-xs text-indigo-700 dark:text-indigo-400 space-y-1">
                    <li><span class="font-bold text-purple-600">Giro / Kliring (Ungu):</span> Dana belum tentu masuk. Cek <b>Rekening Koran</b>. Jika cair, klik Setujui.</li>
                    <li><span class="font-bold text-blue-600">Verifikasi Foto (Biru):</span> Cek <b>Foto Bukti Transfer</b>. Jika valid, klik Setujui.</li>
                    <li>Jika ada <b>Giro Ganda</b>, sistem otomatis menolak sisanya saat salah satu disetujui.</li>
                </ul>
            </div>
        </div>
    @endif

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Pihak Terkait</th>
                        <th>Metode & Referensi</th>
                        <th class="text-right">Nominal</th>
                        <th class="text-center">Bukti / Ref</th>
                        <th class="text-center w-40">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingPayments as $payment)
                        @php
                            $isSales = $payment->payment_type === 'Piutang';
                            $id = $isSales ? $payment->payment_id : $payment->id;
                            
                            $approveRoute = $isSales 
                                ? route('admin.payment-clearance.sales.approve', $id) 
                                : route('admin.payment-clearance.purchase.approve', $id);
                            $rejectRoute = $isSales 
                                ? route('admin.payment-clearance.sales.reject', $id) 
                                : route('admin.payment-clearance.purchase.reject', $id);
                                
                            $partyName = $isSales 
                                ? ($payment->salesInvoice->client->client_name ?? '-') 
                                : ($payment->purchaseOrder->supplier->supplier_name ?? '-');
                            $docNumber = $isSales
                                ? ($payment->salesInvoice->invoice_number ?? '-')
                                : ($payment->purchaseOrder->po_number ?? '-');

                            $methodName = $payment->paymentMethod->name ?? '';
                            $isGiro = stripos($methodName, 'Giro') !== false || stripos($methodName, 'Cek') !== false;

                            if ($isGiro) {
                                $statusBadge = '<span class="badge bg-purple-100 text-purple-700 border-purple-200">Giro / Kliring</span>';
                                $confirmMessage = "Pastikan dana Giro/Cek SUDAH CAIR di rekening koran Anda sebelum menyetujui ini.";
                            } else {
                                $statusBadge = '<span class="badge bg-blue-100 text-blue-700 border-blue-200">Verifikasi Foto</span>';
                                $confirmMessage = "Pembayaran akan dicatat sebagai LUNAS dan Jurnal Akuntansi akan diposting.";
                            }
                        @endphp

                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    Input: {{ $payment->created_at->format('H:i') }}
                                </div>
                            </td>
                            <td>
                                @if($isSales)
                                    <div class="text-xs font-bold text-emerald-600 mb-1 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">arrow_downward</i> PIUTANG
                                    </div>
                                @else
                                    <div class="text-xs font-bold text-rose-600 mb-1 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">arrow_upward</i> HUTANG
                                    </div>
                                @endif
                                
                                @if($viewMode === 'pending')
                                    {!! $statusBadge !!}
                                @endif
                            </td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200 truncate max-w-[180px]" title="{{ $partyName }}">
                                    {{ $partyName }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Doc: <span class="font-mono">{{ $docNumber }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ $payment->paymentMethod->name ?? 'Manual' }}
                                </div>
                                @if($payment->companyBankAccount)
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">account_balance</i>
                                        {{ $payment->companyBankAccount->bank_name }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="font-bold font-mono text-base {{ $isSales ? 'text-emerald-600' : 'text-rose-600' }}">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex flex-col items-center gap-1 justify-center h-full">
                                    <div class="flex gap-1">
                                        @if($payment->proof_of_payment_path)
                                            <button type="button" 
                                                onclick="openDetailModal('image', '{{ asset('storage/' . $payment->proof_of_payment_path) }}')" 
                                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors flex items-center justify-center border border-indigo-200 shadow-sm"
                                                title="Lihat Foto Bukti">
                                                <i class="material-icons text-sm">image</i>
                                            </button>
                                        @endif
    
                                        @if($payment->reference_number)
                                            <button type="button" 
                                                onclick="openDetailModal('text', '{{ $payment->reference_number }}')" 
                                                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-colors flex items-center justify-center border border-slate-200 shadow-sm"
                                                title="Lihat No. Referensi">
                                                <i class="material-icons text-sm">description</i>
                                            </button>
                                        @endif
                                    </div>
                                    @if(!$payment->proof_of_payment_path && !$payment->reference_number)
                                        <span class="text-slate-300 text-xs italic">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($viewMode === 'pending')
                                    <div class="flex items-center justify-center gap-2">
                                        <form action="{{ $approveRoute }}" method="POST" onsubmit="return false;">
                                            @csrf
                                            <button type="button" onclick="confirmApprove(this, '{{ $confirmMessage }}')" 
                                                    class="w-9 h-9 rounded-full flex items-center justify-center bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all shadow-sm group"
                                                    title="Setujui">
                                                <i class="material-icons text-[18px] group-hover:scale-110 transition-transform">check</i>
                                            </button>
                                        </form>
                                        <form action="{{ $rejectRoute }}" method="POST" onsubmit="return false;">
                                            @csrf
                                            <button type="button" onclick="confirmReject(this)" 
                                                    class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-600 hover:text-white hover:border-rose-600 transition-all shadow-sm group"
                                                    title="Tolak">
                                                <i class="material-icons text-[18px] group-hover:scale-110 transition-transform">close</i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    @if($payment->status == 'completed')
                                        <span class="badge bg-emerald-100 text-emerald-700 border-emerald-200 w-full justify-center">
                                            <i class="material-icons text-[14px] mr-1">check_circle</i> Diterima
                                        </span>
                                    @elseif($payment->status == 'failed')
                                        <span class="badge bg-rose-100 text-rose-700 border-rose-200 w-full justify-center">
                                            <i class="material-icons text-[14px] mr-1">cancel</i> Ditolak
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">{{ $payment->status }}</span>
                                    @endif

                                    @if($payment->receivedBy)
                                        <div class="text-[10px] text-slate-400 mt-1">
                                            Oleh: {{ explode(' ', $payment->receivedBy->full_name)[0] }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-12">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                                        <i class="material-icons text-3xl text-slate-300">
                                            {{ $viewMode === 'pending' ? 'fact_check' : 'history' }}
                                        </i>
                                    </div>
                                    <h3 class="text-slate-600 font-bold dark:text-slate-300">Data Kosong</h3>
                                    <p class="text-sm mt-1">
                                        {{ $viewMode === 'pending' ? 'Tidak ada pembayaran pending.' : 'Belum ada riwayat audit.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ✅ MODAL DETAIL (DIPINDAHKAN KE BODY OLEH JS) --}}
    <div id="detailModal" class="relative z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" onclick="closeDetailModal()"></div>

        {{-- Wrapper Utama (Scroll Viewport) --}}
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                
                {{-- Modal Card --}}
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i id="modalIcon" class="material-icons text-indigo-500">visibility</i>
                            <span id="modalTitle">Detail Bukti</span>
                        </h3>
                        <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1 transition-colors dark:hover:bg-slate-700 dark:hover:text-slate-300">
                            <i class="material-icons">close</i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 bg-slate-50 dark:bg-slate-900">
                        <div id="imageContainer" class="hidden w-full flex justify-center">
                            <img id="previewImage" src="" class="w-full h-auto rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                        </div>
                        <div id="textContainer" class="hidden w-full text-center">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-bold mb-3">Nomor Referensi / Catatan</p>
                            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm relative group">
                                <p id="previewText" class="text-2xl font-mono font-bold text-slate-800 dark:text-white break-all select-all"></p>
                                <button onclick="copyToClipboard()" class="absolute top-2 right-2 text-slate-300 hover:text-indigo-600 transition-colors" title="Salin">
                                    <i class="material-icons text-sm">content_copy</i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-white px-5 py-3 sm:flex sm:flex-row-reverse dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" class="w-full inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 sm:mt-0 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600 dark:hover:bg-slate-600 transition-colors" onclick="closeDetailModal()">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // ✅ 1. PINDAHKAN MODAL KE BODY SAAT LOAD (SOLUSI SCROLLING)
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('detailModal');
        if (modal) {
            document.body.appendChild(modal);
        }
    });

    function openDetailModal(type, content) {
        const modal = document.getElementById('detailModal');
        const imgContainer = document.getElementById('imageContainer');
        const textContainer = document.getElementById('textContainer');
        const imgEl = document.getElementById('previewImage');
        const textEl = document.getElementById('previewText');
        const titleEl = document.getElementById('modalTitle');
        const iconEl = document.getElementById('modalIcon');

        imgContainer.classList.add('hidden');
        textContainer.classList.add('hidden');

        if (type === 'image') {
            titleEl.textContent = 'Foto Bukti Transfer';
            iconEl.textContent = 'image';
            imgEl.src = content;
            imgContainer.classList.remove('hidden');
        } else {
            titleEl.textContent = 'Detail Referensi';
            iconEl.textContent = 'description';
            textEl.textContent = content;
            textContainer.classList.remove('hidden');
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => { 
            document.getElementById('previewImage').src = ''; 
            document.getElementById('previewText').textContent = '';
        }, 150);
    }

    function copyToClipboard() {
        const text = document.getElementById('previewText').innerText;
        navigator.clipboard.writeText(text);
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") closeDetailModal();
    });

    function confirmApprove(btn, message) {
        window.confirmDialog({
            title: 'Setujui Pembayaran?',
            text: message,
            icon: 'question',
            confirmButtonText: 'Ya, Setujui',
            confirmButtonColor: '#10b981',
            showCancelButton: true,
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (res.isConfirmed) btn.closest('form').submit();
        });
    }

    function confirmReject(btn) {
        window.confirmDialog({
            title: 'Tolak Pembayaran?',
            text: "Status akan diubah menjadi Gagal.",
            icon: 'warning',
            confirmButtonText: 'Ya, Tolak',
            confirmButtonColor: '#ef4444',
            showCancelButton: true,
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (res.isConfirmed) btn.closest('form').submit();
        });
    }
</script>
@endpush