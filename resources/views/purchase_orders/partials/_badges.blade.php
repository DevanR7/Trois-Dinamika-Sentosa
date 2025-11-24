{{-- 1. LOGIKA STATUS PESANAN (BARANG) --}}
@if($po->status == 'completed') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200 uppercase">
        Diterima
    </span>
@elseif($po->status == 'draft') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase">
        Draft
    </span>
@elseif($po->status == 'ordered') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase">
        Dipesan
    </span>
@elseif($po->status == 'cancelled') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200 uppercase">
        Batal
    </span>
@else 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">
        {{ $po->status }}
    </span>
@endif

{{-- 2. LOGIKA STATUS PEMBAYARAN --}}
@if($po->payment_status == 'paid') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 uppercase">
        Lunas
    </span>
@elseif($po->payment_status == 'partially_paid') 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200 uppercase">
        Cicilan
    </span>
@else 
    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 border border-red-200 uppercase">
        Belum Lunas
    </span>
@endif