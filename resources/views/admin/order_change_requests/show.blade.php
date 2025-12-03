@extends('admin.layouts.app')

@section('title', 'Detail Review Pesanan')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.order-change-requests.index') }}" class="hover:text-indigo-600 transition-colors font-medium">Review Pesanan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                {{-- PERBAIKAN: Gunakan $changeRequest->order --}}
                <span class="font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ $changeRequest->order->order_number }}</span>
                
                @php
                    $statusMap = [
                        'pending' => ['class' => 'status-pending', 'icon' => 'schedule', 'label' => 'Menunggu'],
                        'approved' => ['class' => 'status-approved', 'icon' => 'check_circle', 'label' => 'Disetujui'],
                        'rejected' => ['class' => 'status-rejected', 'icon' => 'cancel', 'label' => 'Ditolak'],
                    ];
                    $st = $statusMap[$changeRequest->status] ?? ['class' => 'status-draft', 'icon' => 'help', 'label' => 'Unknown'];
                @endphp
                <span class="{{ $st['class'] }} flex items-center gap-1 text-xs h-fit px-2.5 py-1 rounded-md">
                    <i class="material-icons text-[14px]">{{ $st['icon'] }}</i> {{ $st['label'] }}
                </span>
            </h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.order-change-requests.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            
            @if($changeRequest->status == 'pending')
            <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" 
                    class="h-[48px] px-6 bg-red-50 border border-red-200 text-red-700 font-bold rounded-lg hover:bg-red-100 transition-all text-sm flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">cancel</i> Tolak
            </button>
            
            <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST" class="form-confirm">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" data-title="Setujui Permintaan?" data-text="Perubahan akan diterapkan ke pesanan asli."
                        class="h-[48px] px-6 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5">
                    <i class="material-icons text-[18px]">check_circle</i> Setujui
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI: DETAIL ITEM PERUBAHAN (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- INFO PERMINTAAN --}}
            <div class="dashboard-card p-6 shadow-sm border-l-4 border-indigo-500">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Detail Permintaan</h3>
                
                <div class="grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <label class="block text-xs text-slate-500 uppercase mb-1">Tipe Request</label>
                        <span class="font-bold {{ $changeRequest->request_type == 'cancel' ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $changeRequest->request_type == 'cancel' ? 'Pembatalan Pesanan' : 'Revisi Item' }}
                        </span>
                    </div>
                    <div>
                         <label class="block text-xs text-slate-500 uppercase mb-1">Waktu Pengajuan</label>
                         <span class="font-mono text-slate-700">{{ $changeRequest->created_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>

                @if($changeRequest->client_notes)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <label class="block text-xs text-slate-500 uppercase mb-1">Catatan Klien</label>
                    <p class="text-slate-600 italic bg-slate-50 p-3 rounded border border-slate-100">"{{ $changeRequest->client_notes }}"</p>
                </div>
                @endif
            </div>
            
            {{-- TABEL ITEM (Hanya jika tipe modify) --}}
            @if($changeRequest->request_type == 'modify')
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex justify-between items-center">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-lg">edit_note</i> Item yang Diubah
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-white border-b border-slate-200">
                            <tr>
                                <th class="pl-6 text-left">Produk</th>
                                <th class="text-center">Qty Lama</th>
                                <th class="text-center">Qty Baru</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($changeRequest->items as $item)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="pl-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $item->product->product_code ?? '' }}</div>
                                </td>
                                <td class="py-4 text-center text-slate-500 font-mono">
                                    {{ $item->action == 'add' ? '-' : ($item->original_quantity ?? 0) }}
                                </td>
                                <td class="py-4 text-center font-bold text-slate-800 font-mono">
                                    {{ $item->action == 'remove' ? '-' : $item->requested_quantity }}
                                </td>
                                <td class="py-4 text-center">
                                    @if($item->action == 'add')
                                        <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-xs font-bold">BARU</span>
                                    @elseif($item->action == 'remove')
                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">HAPUS</span>
                                    @else
                                        <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs font-bold">UBAH</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN: INFO & AKSI (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <i class="material-icons text-[20px]">person</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Info Pemesan</h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <i class="material-icons text-xl">business</i>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Klien</label>
                            <h4 class="text-base font-bold text-slate-900 leading-tight">{{ $changeRequest->client->client_name }}</h4>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-dashed border-slate-200 text-center">
                        <a href="{{ route('admin.sales-orders.show', $changeRequest->order_id) }}" target="_blank" class="text-sm text-indigo-600 hover:underline font-bold flex items-center justify-center gap-1">
                            Lihat Pesanan Asli <i class="material-icons text-sm">open_in_new</i>
                        </a>
                    </div>
                </div>
            </div>

            @if($changeRequest->status != 'pending')
            <div class="dashboard-card p-6 bg-slate-50 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Diproses Oleh</h4>
                <div class="flex items-center gap-2 mb-4">
                    <i class="material-icons text-slate-400">admin_panel_settings</i>
                    <span class="font-bold text-slate-700">{{ $changeRequest->processor->full_name ?? 'Sistem' }}</span>
                </div>
                
                @if($changeRequest->admin_notes)
                    <h4 class="text-xs font-bold text-slate-500 uppercase mb-1">Catatan Admin</h4>
                    <p class="text-sm text-slate-600 italic">"{{ $changeRequest->admin_notes }}"</p>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>

{{-- MODAL TOLAK --}}
<div id="rejectModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
    
    <div class="flex min-h-full items-center justify-center p-4 text-center">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
            
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="material-icons text-red-600 text-xl">warning_amber</i>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg font-bold leading-6 text-slate-900">Tolak Permintaan?</h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500">
                                Permintaan perubahan akan ditolak dan pesanan akan tetap pada kondisi sebelumnya.
                            </p>
                            
                            <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                
                                <label for="admin_notes" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Alasan Penolakan (Wajib)</label>
                                <textarea name="admin_notes" id="admin_notes" rows="3" class="form-textarea w-full text-sm" placeholder="Jelaskan alasan penolakan..." required></textarea>
                                
                                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:w-auto">Ya, Tolak</button>
                                    <button type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto" onclick="document.getElementById('rejectModal').classList.add('hidden')">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush