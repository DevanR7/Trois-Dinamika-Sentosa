@extends('admin.layouts.app')

@section('title', 'Manajemen Supplier')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Supplier</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar vendor dan pemasok barang.</p>
        </div>
        
        {{-- ACTION BUTTONS --}}
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            @if(request('status') === 'deleted')
                <a href="{{ route('admin.suppliers.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center gap-2">
                    <i class="material-icons text-[18px]">arrow_back</i> Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('admin.suppliers.index', ['status' => 'deleted']) }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm flex items-center gap-2">
                    <i class="material-icons text-[18px] text-slate-400">archive</i> Arsip
                </a>
                <a href="{{ route('admin.suppliers.create') }}" 
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">add</i> Tambah Supplier
                </a>
            @endif
        </div>
    </div>

    {{-- LIST DATA (MENGGUNAKAN COMPONENT) --}}
    <div class="space-y-4">
        @forelse ($suppliers as $supplier)
        
            {{-- Panggil Component Accordion --}}
            <x-ui.accordion-card id="supp-{{ $supplier->supplier_id }}">
                
                {{-- SLOT: HEADER (Tampilan luar saat tertutup) --}}
                <x-slot name="header">
                {{-- Kiri: Info Utama --}}
                <div class="flex items-center gap-4 pr-4 flex-grow min-w-0">
                    {{-- Icon --}}
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                        <i class="material-icons text-xl">business</i>
                    </div>
                    {{-- Teks Nama --}}
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-800 truncate">{{ $supplier->supplier_name }}</h3>
                        <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5 truncate">
                            <i class="material-icons text-[14px]">person</i> {{ $supplier->person_in_charge ?? 'Belum ada PIC' }}
                        </p>
                    </div>
                </div>

                {{-- Kanan: Saldo & Status (Diperbaiki) --}}
                <div class="flex items-center gap-3 sm:gap-5 ml-auto flex-shrink-0 pl-4">
                    
                    {{-- Blok Saldo --}}
                    <div class="flex flex-col items-end text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-tight mb-0.5">
                            Saldo Deposit
                        </span>
                        <span class="text-sm font-bold font-mono {{ $supplier->balance > 0 ? 'text-emerald-600' : 'text-slate-600' }} leading-tight">
                            Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Garis Pemisah Vertikal (Agar lebih rapi) --}}
                    <div class="hidden sm:block w-px h-8 bg-slate-200"></div>

                    {{-- Badge Status --}}
                    <div>
                        @if($supplier->trashed())
                            <span class="status-badge status-rejected">
                                <i class="material-icons text-[12px]">archive</i> <span class="hidden sm:inline">Arsip</span>
                            </span>
                        @else
                            <span class="status-badge status-completed">
                                <i class="material-icons text-[12px]">check_circle</i> <span class="hidden sm:inline">Aktif</span>
                            </span>
                        @endif
                    </div>

                </div>
            </x-slot>

                {{-- SLOT: UTAMA (Isi detail saat dibuka) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    
                    {{-- Kolom 1: Kontak --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 tracking-wide">Kontak & Alamat</h4>
                        <ul class="space-y-2 text-slate-600">
                            <li class="flex items-center gap-2">
                                <i class="material-icons text-indigo-400 text-[16px]">phone</i> 
                                {{ $supplier->phone_number ?? '-' }}
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="material-icons text-indigo-400 text-[16px]">location_on</i> 
                                <span class="leading-relaxed">{{ $supplier->address ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Kolom 2: Bank --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 tracking-wide">Informasi Bank</h4>
                        <ul class="space-y-2 text-slate-600">
                            <li class="flex justify-between border-b border-dashed border-slate-200 pb-1">
                                <span class="text-xs text-slate-400">NPWP</span> 
                                <span class="font-mono text-xs">{{ $supplier->npwp ?? '-' }}</span>
                            </li>
                            <li class="flex justify-between border-b border-dashed border-slate-200 pb-1">
                                <span class="text-xs text-slate-400">Bank</span> 
                                <span>{{ $supplier->bank_name ?? '-' }}</span>
                            </li>
                            <li class="flex justify-between border-b border-dashed border-slate-200 pb-1">
                                <span class="text-xs text-slate-400">Rekening</span> 
                                <span class="font-mono font-medium">{{ $supplier->account_number ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Kolom 3: Action Buttons --}}
                    <div class="flex flex-col gap-2 justify-center md:border-l md:border-slate-200 md:pl-6">
                        @if($supplier->trashed())
                            {{-- Tombol RESTORE (Hijau) --}}
                            <form action="{{ route('admin.suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-confirm w-full">
                                @csrf @method('PATCH')
                                <button type="submit" 
                                        data-title="Pulihkan Supplier?"
                                        data-text="Supplier <b>{{ $supplier->supplier_name }}</b> akan dikembalikan ke status aktif."
                                        data-btn-text="Ya, Pulihkan!"
                                        data-btn-color="#10b981"
                                        data-icon="question"
                                        class="w-full px-3 py-2 bg-white border border-emerald-300 text-emerald-700 rounded-lg text-xs font-bold hover:bg-emerald-50 transition flex items-center justify-center gap-2 shadow-sm">
                                    <i class="material-icons text-[16px]">restore</i> Pulihkan
                                </button>
                            </form>
                        @else
                            {{-- Tombol DETAIL & EDIT --}}
                            <div class="flex gap-2">
                                <a href="{{ route('admin.suppliers.show', $supplier->supplier_id) }}" 
                                   class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center shadow-sm">
                                    Detail
                                </a>
                                <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" 
                                   class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition text-center shadow-sm">
                                    Edit
                                </a>
                            </div>
                            
                            {{-- Tombol ARSIP (Merah) --}}
                            <form action="{{ route('admin.suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-confirm w-full">
                                @csrf @method('DELETE')
                                <button type="submit" 
                                        data-name="{{ $supplier->supplier_name }}"
                                        data-title="Arsipkan Supplier?"
                                        data-text="Supplier <b>{{ $supplier->supplier_name }}</b> akan dipindahkan ke arsip."
                                        data-btn-text="Ya, Arsipkan!"
                                        data-btn-color="#ef4444"
                                        class="w-full px-3 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-xs font-bold hover:bg-red-50 hover:border-red-300 transition flex items-center justify-center gap-2 shadow-sm">
                                    <i class="material-icons text-[16px]">archive</i> Arsipkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

            </x-ui.accordion-card>

        @empty
            {{-- Empty State --}}
            <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <i class="material-icons text-slate-300 text-4xl">inventory</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Belum ada Supplier</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-xs mx-auto">Mulai tambahkan data vendor atau pemasok Anda di sini.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $suppliers->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Hanya menangani Session Toast
    // Animasi dropdown & SweetAlert sudah otomatis ditangani app.js & component
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush