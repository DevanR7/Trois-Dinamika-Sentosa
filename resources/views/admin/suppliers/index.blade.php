@extends('admin.layouts.app')

@section('title', 'Data Supplier')

@section('content')
    <div class="card animate-enter h-full flex flex-col">
        
        {{-- HEADER: Judul & Actions --}}
        <div class="card-header flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="card-header-title">Data Supplier</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Database mitra pemasok, kontak, dan info pembayaran.</p>
            </div>
            
            <div class="flex items-center gap-2">
                {{-- Filter Arsip --}}
                @if(request('status') == 'deleted')
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="material-icons text-sm">visibility</i> Lihat Aktif
                    </a>
                @else
                    <a href="{{ route('admin.suppliers.index', ['status' => 'deleted']) }}" class="btn btn-secondary btn-sm text-rose-500 hover:bg-rose-50 border-rose-200">
                        <i class="material-icons text-sm">delete_outline</i> Sampah
                    </a>
                @endif

                <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                    <i class="material-icons text-sm">person_add</i> Tambah Supplier
                </a>
            </div>
        </div>

        {{-- FILTER SECTION --}}
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
            <form action="{{ route('admin.suppliers.index') }}" method="GET" class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="form-input pl-10" 
                       placeholder="Cari supplier, PIC, telepon, atau nomor rekening...">
            </form>
        </div>

        {{-- =====================================================================
             1. MOBILE VIEW: CARD GRID
             Tombol Kotak 1:1 dan Warna Kontras
             ===================================================================== --}}
        <div class="block md:hidden p-4 bg-slate-50 dark:bg-slate-900/50 flex-1">
            @if($suppliers->isEmpty())
                <div class="text-center py-12 text-slate-400">
                    <i class="material-icons text-4xl mb-2">person_off</i>
                    <p>Data supplier tidak ditemukan.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-3">
                    @foreach($suppliers as $supplier)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 flex flex-col gap-3">
                        
                        {{-- Header Card --}}
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center font-bold text-lg shrink-0">
                                    {{ substr($supplier->supplier_name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-white leading-snug">
                                        {{ $supplier->supplier_name }}
                                    </h4>
                                    <div class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">person</i> 
                                        {{ $supplier->person_in_charge ?: 'Tanpa PIC' }}
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Badge Jumlah Produk --}}
                            <span class="badge bg-slate-100 text-slate-600 text-[10px] px-2 py-1 rounded-full border border-slate-200">
                                {{ $supplier->products_count ?? 0 }} Produk
                            </span>
                        </div>

                        {{-- Info Saldo & Bank --}}
                        <div class="bg-slate-50 dark:bg-slate-700/50 p-2.5 rounded-lg border border-slate-100 dark:border-slate-600">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Saldo Hutang</span>
                                <span class="font-bold text-sm {{ $supplier->balance > 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                                    Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                                </span>
                            </div>
                            @if($supplier->bank_name)
                                <div class="flex items-center gap-2 pt-1 border-t border-slate-200 dark:border-slate-600 mt-1">
                                    <span class="text-[10px] font-bold text-indigo-500">{{ $supplier->bank_name }}</span>
                                    <span class="text-[10px] font-mono text-slate-500">{{ $supplier->account_number }}</span>
                                    <button class="ml-auto text-slate-400 hover:text-indigo-500" onclick="navigator.clipboard.writeText('{{ $supplier->account_number }}'); window.showToast('No Rekening Disalin!');">
                                        <i class="material-icons text-sm">content_copy</i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons (MOBILE) --}}
                        <div class="flex items-center justify-between gap-2 mt-1 pt-3 border-t border-slate-100 dark:border-slate-700">
                            
                            {{-- GROUP KIRI: Contact (Call & WA) --}}
                            <div class="flex gap-2">
                                @if($supplier->phone_number)
                                    {{-- Call --}}
                                    <a href="tel:{{ $supplier->phone_number }}" 
                                       class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 transition-colors shadow-sm">
                                        <i class="material-icons text-lg">call</i>
                                    </a>
                                    {{-- WA --}}
                                    <a href="{{ $supplier->wa_link }}" target="_blank" 
                                       class="w-10 h-10 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 transition-colors shadow-sm">
                                        <i class="material-icons text-lg">chat</i>
                                    </a>
                                @else
                                    <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-50 text-slate-300 cursor-not-allowed border border-slate-100">
                                        <i class="material-icons text-lg">phonelink_erase</i>
                                    </div>
                                @endif
                            </div>

                            {{-- GROUP KANAN: CRUD (View, Edit, Delete) --}}
                            <div class="flex gap-2">
                                {{-- View Detail --}}
                                <a href="{{ route('admin.suppliers.show', $supplier->supplier_id) }}" 
                                   class="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors shadow-sm">
                                    <i class="material-icons text-lg">visibility</i>
                                </a>

                                {{-- Edit --}}
                                <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" 
                                   class="w-10 h-10 flex items-center justify-center rounded-lg bg-amber-100 text-amber-600 hover:bg-amber-200 transition-colors shadow-sm">
                                    <i class="material-icons text-lg">edit</i>
                                </a>
                                
                                {{-- Delete --}}
                                <button type="button" onclick="confirmDeleteMobile('{{ route('admin.suppliers.destroy', $supplier->supplier_id) }}')" 
                                        class="w-10 h-10 flex items-center justify-center rounded-lg bg-rose-100 text-rose-600 hover:bg-rose-200 transition-colors shadow-sm">
                                    <i class="material-icons text-lg">delete</i>
                                </button>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- =====================================================================
             2. DESKTOP VIEW: TABLE MODERN
             Tombol Kotak 1:1 (w-8 h-8)
             ===================================================================== --}}
        <div class="hidden md:block table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="pl-6 w-14">#</th>
                        <th>Supplier / PIC</th>
                        <th>Kontak / Alamat</th>
                        <th>Info Pembayaran (Bank)</th>
                        <th class="text-right">Saldo Hutang</th>
                        <th class="text-center">Produk</th>
                        <th class="text-right pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td class="pl-6 align-middle text-slate-500">
                            {{ $suppliers->firstItem() + $loop->index }}
                        </td>
                        
                        {{-- Kolom Nama --}}
                        <td class="align-middle">
                            <div class="font-bold text-slate-800 dark:text-white text-sm">
                                {{ $supplier->supplier_name }}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                <i class="material-icons text-[10px]">person</i> 
                                {{ $supplier->person_in_charge ?: '-' }}
                            </div>
                        </td>

                        {{-- Kolom Kontak --}}
                        <td class="align-middle">
                            @if($supplier->phone_number)
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono text-slate-600 dark:text-slate-300">{{ $supplier->phone_number }}</span>
                                    <a href="{{ $supplier->wa_link }}" target="_blank" class="text-emerald-500 hover:text-emerald-600" title="Chat WhatsApp">
                                        <i class="material-icons text-sm">chat</i>
                                    </a>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 italic">No Phone</span>
                            @endif
                            
                            @if($supplier->address)
                                <div class="text-[10px] text-slate-400 truncate max-w-[180px]" title="{{ $supplier->address }}">
                                    {{ Str::limit($supplier->address, 25) }}
                                </div>
                            @endif
                        </td>

                        {{-- Kolom Bank --}}
                        <td class="align-middle">
                            @if($supplier->bank_name)
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $supplier->bank_name }}</span>
                                    <span class="text-xs font-mono text-slate-500 flex items-center gap-1">
                                        {{ $supplier->account_number }}
                                        <i class="material-icons text-[10px] cursor-pointer hover:text-indigo-500" 
                                           onclick="navigator.clipboard.writeText('{{ $supplier->account_number }}'); window.showToast('Disalin!')">content_copy</i>
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                            @if($supplier->npwp)
                                <div class="text-[9px] text-slate-400 mt-0.5">NPWP: {{ $supplier->npwp }}</div>
                            @endif
                        </td>

                        {{-- Kolom Saldo --}}
                        <td class="align-middle text-right">
                            <span class="font-bold text-sm {{ $supplier->balance > 0 ? 'text-rose-500' : 'text-slate-600 dark:text-slate-400' }}">
                                Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- Kolom Jumlah Produk --}}
                        <td class="align-middle text-center">
                            <span class="badge bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $supplier->products_count ?? 0 }}
                            </span>
                        </td>

                        {{-- Kolom Aksi (Desktop) --}}
                        <td class="align-middle text-right pr-6">
                            <div class="flex items-center justify-end gap-2">
                                @if($supplier->deleted_at)
                                    {{-- Restore Button --}}
                                    <form action="{{ route('admin.suppliers.restore', $supplier->supplier_id) }}" method="POST">
                                        @csrf
                                        <button class="w-8 h-8 flex items-center justify-center rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors" title="Pulihkan">
                                            <i class="material-icons text-lg">restore</i>
                                        </button>
                                    </form>
                                @else
                                    {{-- View Detail --}}
                                    <a href="{{ route('admin.suppliers.show', $supplier->supplier_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Lihat History">
                                        <i class="material-icons text-lg">receipt_long</i>
                                    </a>
                                    
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-600 hover:bg-amber-50 transition-colors" title="Edit">
                                        <i class="material-icons text-lg">edit</i>
                                    </a>
                                    
                                    {{-- Delete --}}
                                    <button type="button" 
                                            onclick="confirmDeleteMobile('{{ route('admin.suppliers.destroy', $supplier->supplier_id) }}')" 
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50 transition-colors" title="Arsipkan">
                                        <i class="material-icons text-lg">delete</i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="material-icons text-4xl mb-2 opacity-30">person_off</i>
                                <span class="text-sm">Belum ada data supplier.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($suppliers->hasPages())
            <div class="px-4 py-4 border-t border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-b-xl">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    {{-- HIDDEN FORM FOR DELETE --}}
    <form id="delete-form" action="" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
    function confirmDeleteMobile(url) {
        window.confirmDialog({
            title: 'Arsipkan Supplier?',
            text: 'Data akan dipindahkan ke sampah. Pastikan tidak ada hutang aktif.',
            confirmText: 'Ya, Arsipkan',
            cancelText: 'Batal',
            confirmColor: 'danger',
            icon: 'warning'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-form');
                form.action = url;
                form.submit();
            }
        });
    }
</script>
@endpush