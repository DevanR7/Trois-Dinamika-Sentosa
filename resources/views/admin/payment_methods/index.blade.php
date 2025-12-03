@extends('admin.layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Metode Pembayaran</h1>
            <p class="text-slate-500 text-sm mt-1">Atur cara pembayaran yang diterima (Transfer, Cash, Giro, dll).</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.payment-methods.archived.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">archive</i> Arsip
            </a>
            <a href="{{ route('admin.payment-methods.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
                <span>Tambah Metode</span>
            </a>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Nama Metode</th>
                        <th>Tipe</th>
                        <th>Konfigurasi Wajib</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($paymentMethods as $method)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                    {{ $method->name }}
                                </span>
                            </td>
                            <td class="py-4">
                                @if($method->type == 'direct')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Langsung (Direct)</span>
                                @elseif($method->type == 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">Tertunda (Pending)</span>
                                @elseif($method->type == 'gateway')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">Gateway</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600">{{ $method->type }}</span>
                                @endif
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                @if($method->required_fields_config == 'none')
                                    <span class="text-slate-400 italic">Standar</span>
                                @elseif($method->required_fields_config == 'proof_only')
                                    <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded border border-slate-200 text-xs font-medium"><i class="material-icons text-[14px]">image</i> Bukti Foto</span>
                                @elseif($method->required_fields_config == 'reference_only')
                                    <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded border border-slate-200 text-xs font-medium"><i class="material-icons text-[14px]">tag</i> No. Ref</span>
                                @elseif($method->required_fields_config == 'proof_and_reference')
                                    <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded border border-slate-200 text-xs font-medium"><i class="material-icons text-[14px]">image</i> + <i class="material-icons text-[14px]">tag</i></span>
                                @endif
                            </td>
                            <td class="py-4 text-center">
                                @if ($method->is_active)
                                    <span class="status-completed flex items-center justify-center gap-1 w-fit mx-auto px-2 py-0.5">
                                        <i class="material-icons text-[12px]">check_circle</i> Aktif
                                    </span>
                                @else
                                    <span class="status-draft flex items-center justify-center gap-1 w-fit mx-auto px-2 py-0.5">
                                        <i class="material-icons text-[12px]">block</i> Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.payment-methods.edit', $method->payment_method_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" 
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('admin.payment-methods.destroy', $method->payment_method_id) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ $method->name }}" 
                                                data-title="Arsipkan Metode?" 
                                                data-text="Metode ini akan disembunyikan dari pilihan pembayaran."
                                                data-btn-text="Ya, Arsipkan"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                title="Arsipkan">
                                            <i class="material-icons text-[16px]">archive</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">payments</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan metode pembayaran baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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