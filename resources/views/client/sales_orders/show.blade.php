@extends('client.layouts.app')

@section('title', 'Detail Pesanan #' . $order->order_number)

@section('content')

    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('client.sales-orders.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit">
            <i class="material-icons text-sm">arrow_back</i>
            <span class="text-sm font-medium">Kembali ke Daftar</span>
        </a>
    </div>

    {{-- =====================================================================
         1. BLOK ALERT STATUS & INFO PENTING
         ===================================================================== --}}
    
    {{-- A. Alert Penolakan Pesanan (Jika status REJECTED) --}}
    @if($order->status === 'rejected' && $order->notes)
        <div class="p-4 mb-6 rounded-xl bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800 flex items-start gap-4">
            <div class="bg-red-100 text-red-600 rounded-full p-1 shrink-0">
                <i class="material-icons text-lg">error_outline</i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-red-800 dark:text-red-400">Pesanan Ditolak</h4>
                <p class="text-xs text-red-700 dark:text-red-500 mt-1">
                    Mohon maaf, pesanan ini tidak dapat diproses.
                </p>
                <div class="mt-2 text-sm text-red-800 italic font-medium bg-red-100/50 p-2 rounded border border-red-200 dark:border-red-700">
                    "{{ $order->notes }}"
                </div>
            </div>
        </div>
    @endif

    {{-- B. Alert Permintaan Perubahan PENDING --}}
    @php
        $pendingRequest = $order->changeRequests->where('status', 'pending')->first();
    @endphp
    
    @if($pendingRequest)
        <div class="p-4 mb-6 rounded-xl bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 flex items-start gap-4">
            <div class="bg-blue-100 text-blue-600 rounded-full p-1 shrink-0">
                <i class="material-icons text-lg">info</i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-blue-800 dark:text-blue-400">Permintaan Perubahan Sedang Diproses</h4>
                <p class="text-xs text-blue-700 dark:text-blue-500 mt-1">
                    Anda telah mengajukan perubahan untuk pesanan ini pada {{ $pendingRequest->created_at->format('d M Y H:i') }}. 
                    Harap tunggu konfirmasi dari Admin/Sales.
                </p>
                @if($pendingRequest->client_notes)
                     <p class="text-xs text-blue-600 italic mt-2">Catatan Anda: "{{ $pendingRequest->client_notes }}"</p>
                @endif
            </div>
        </div>
    @endif

    {{-- C. Alert Permintaan Perubahan DITOLAK (Terakhir) --}}
    {{-- Jika order aktif tapi revisi terakhir ditolak, klien perlu tahu alasannya --}}
    @php
        $lastRejectedRequest = $order->changeRequests->where('status', 'rejected')->sortByDesc('created_at')->first();
    @endphp

    @if($lastRejectedRequest && !$pendingRequest && $order->status !== 'rejected')
        <div class="p-4 mb-6 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 flex items-start gap-4">
            <div class="bg-amber-100 text-amber-600 rounded-full p-1 shrink-0">
                <i class="material-icons text-lg">warning</i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400">Permintaan Perubahan Ditolak</h4>
                <p class="text-xs text-amber-700 dark:text-amber-500 mt-1">
                    Pengajuan revisi terakhir Anda pada {{ $lastRejectedRequest->created_at->format('d M Y') }} tidak dapat disetujui.
                </p>
                @if($lastRejectedRequest->admin_notes)
                    <div class="mt-2 text-sm text-amber-800 italic font-medium bg-amber-100/50 p-2 rounded border border-amber-200 dark:border-amber-700">
                        Alasan Admin: "{{ $lastRejectedRequest->admin_notes }}"
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- =====================================================================
         2. MAIN CONTENT GRID
         ===================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Left: Order Items --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="card">
                <div class="card-header justify-between">
                    <h3 class="card-header-title">Rincian Produk</h3>
                    
                    {{-- Status Badge --}}
                    @php
                        $badgeClass = match($order->status) {
                            'pending' => 'badge-warning',
                            'approved' => 'badge-primary',
                            'invoiced' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending_review' => 'badge-info',
                            default => 'badge-secondary',
                        };
                        $statusLabel = match($order->status) {
                            'invoiced' => 'Sudah Faktur',
                            'pending_review' => 'Menunggu Review',
                            'rejected' => 'Ditolak',
                            default => ucfirst($order->status)
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} uppercase">
                        {{ $statusLabel }}
                    </span>
                </div>
                
                <div class="table-container border-0 shadow-none rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-right">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-700 dark:text-white">
                                            {{ $item->product->product_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $item->product->product_code }}
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($item->quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-medium text-slate-800 dark:text-white">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                                <td colspan="3" class="text-right font-bold text-slate-700 dark:text-slate-300 px-6 py-4">
                                    TOTAL PESANAN
                                </td>
                                <td class="text-right font-extrabold text-indigo-600 dark:text-indigo-400 px-6 py-4 text-lg">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Catatan Order (Bukan Alasan Penolakan, tapi catatan umum saat buat order) --}}
                @if($order->notes && $order->status !== 'rejected') 
                    <div class="p-6 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/20">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">Catatan Pesanan</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300 italic">
                            {{ $order->notes }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Riwayat Request Change (Jika ada) --}}
            @if($order->changeRequests->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Riwayat Permintaan Perubahan</h3>
                    </div>
                    <div class="card-body">
                        <div class="relative border-l border-slate-200 dark:border-slate-700 ml-3 space-y-6">
                            @foreach($order->changeRequests as $req)
                                <div class="mb-6 ml-6">
                                    <span class="absolute flex items-center justify-center w-6 h-6 bg-slate-100 rounded-full -left-3 ring-8 ring-white dark:ring-slate-800 dark:bg-slate-700">
                                        @if($req->status == 'approved')
                                            <i class="material-icons text-emerald-500 text-sm">check</i>
                                        @elseif($req->status == 'rejected')
                                            <i class="material-icons text-rose-500 text-sm">close</i>
                                        @else
                                            <i class="material-icons text-amber-500 text-sm">schedule</i>
                                        @endif
                                    </span>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1">
                                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ $req->request_type == 'cancel' ? 'Permintaan Pembatalan' : 'Permintaan Revisi' }}
                                        </h3>
                                        <time class="text-xs text-slate-400">{{ $req->created_at->format('d M Y H:i') }}</time>
                                    </div>
                                    <div class="flex gap-2 items-center mb-2">
                                        <span class="text-xs text-slate-500 dark:text-slate-400">Status:</span>
                                        @if($req->status == 'approved')
                                            <span class="badge badge-success text-[10px]">Disetujui</span>
                                        @elseif($req->status == 'rejected')
                                            <span class="badge badge-danger text-[10px]">Ditolak</span>
                                        @else
                                            <span class="badge badge-warning text-[10px]">Pending</span>
                                        @endif
                                    </div>
                                    
                                    @if($req->client_notes)
                                        <div class="text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                            <span class="font-bold">Anda:</span> "{{ $req->client_notes }}"
                                        </div>
                                    @endif

                                    {{-- Tampilkan Alasan Admin di History juga --}}
                                    @if($req->admin_notes)
                                        <div class="text-xs text-rose-600 bg-rose-50 p-2 rounded border border-rose-100 dark:bg-rose-900/20 dark:border-rose-800 mt-2">
                                            <span class="font-bold">Admin:</span> "{{ $req->admin_notes }}"
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: Actions & Info --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            
            {{-- Sales Info --}}
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg font-bold">
                            {{ substr($order->sales->name ?? 'S', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Dibuat Oleh</p>
                            <p class="font-bold text-slate-800 dark:text-white text-base">
                                {{ $order->sales->name ?? 'Staff Sales' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-xs text-slate-500 space-y-1">
                        <p class="flex items-center gap-2">
                            <i class="material-icons text-sm">email</i> {{ $order->sales->email ?? '-' }}
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="material-icons text-sm">phone</i> {{ $order->sales->phone_number ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            {{-- Hanya bisa request change jika status PENDING atau APPROVED (belum Invoiced) --}}
            {{-- DAN tidak ada request yang sedang pending --}}
            @if(in_array($order->status, ['pending', 'approved']) && !$pendingRequest)
                <div class="card bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800">
                    <div class="card-body">
                        <h4 class="font-bold text-amber-800 dark:text-amber-500 mb-2">Butuh Perubahan?</h4>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mb-4 leading-relaxed">
                            Jika ada kesalahan item atau Anda ingin membatalkan pesanan ini, Anda dapat mengajukan permintaan perubahan kepada Sales.
                        </p>
                        <a href="{{ route('client.sales-orders.requestChange.create', $order->order_id) }}" 
                           class="btn w-full justify-center bg-amber-600 hover:bg-amber-700 text-white border-transparent focus:ring-amber-300">
                            <i class="material-icons text-sm">edit_note</i>
                            Ajukan Perubahan
                        </a>
                    </div>
                </div>
            @endif

            {{-- Jika sudah invoiced, tampilkan link ke invoice --}}
            @if($order->status == 'invoiced' && $order->invoice_id)
                <div class="card bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-800">
                    <div class="card-body text-center">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="material-icons">receipt</i>
                        </div>
                        <h4 class="font-bold text-emerald-800 dark:text-emerald-500 mb-1">Tagihan Tersedia</h4>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mb-4">
                            Pesanan ini telah diproses menjadi Invoice. Silakan lakukan pembayaran.
                        </p>
                        <a href="{{ route('client.invoices.show', $order->invoice_id) }}" class="btn btn-success w-full justify-center">
                            Lihat Invoice
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection