@extends('layouts.app')

@section('title', 'Manajemen Supplier')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen Supplier</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar vendor dan pemasok barang.</p>
        </div>
        <div class="flex gap-3">
            @if(request('status') === 'deleted')
                <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center">
                    <i class="bi bi-arrow-left mr-2"></i> Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('suppliers.index', ['status' => 'deleted']) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center">
                    <i class="bi bi-archive mr-2"></i> Lihat Arsip
                </a>
            @endif
            <a href="{{ route('suppliers.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Supplier
            </a>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
        <div class="flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
            <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
    </div>
    @endif

    {{-- LIST SUPPLIER (ACCORDION STYLE) --}}
    <div class="space-y-4">
        @forelse ($suppliers as $supplier)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
            
            {{-- Header Accordion --}}
            <div class="p-4 cursor-pointer flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-gray-50 transition-colors" onclick="toggleAccordion('supp-{{ $supplier->supplier_id }}')">
                
                {{-- Kiri: Nama --}}
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                        <i class="bi bi-shop text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ $supplier->supplier_name }}</h3>
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <i class="bi bi-person"></i> {{ $supplier->person_in_charge ?? 'Belum ada PIC' }}
                        </p>
                    </div>
                </div>

                {{-- Kanan: Saldo & Status --}}
                <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end">
                    <div class="text-right hidden sm:block">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Saldo Deposit</span>
                        <span class="text-sm font-bold {{ $supplier->balance > 0 ? 'text-green-600' : 'text-gray-400' }}">
                            Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($supplier->trashed())
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Arsip</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Aktif</span>
                        @endif
                        <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="icon-supp-{{ $supplier->supplier_id }}"></i>
                    </div>
                </div>
            </div>

            {{-- Body Accordion --}}
            <div id="supp-{{ $supplier->supplier_id }}" class="hidden bg-gray-50 border-t border-gray-100 p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    
                    {{-- Detail Kontak --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Kontak & Alamat</h4>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start gap-2"><i class="bi bi-telephone text-indigo-400 mt-0.5"></i> {{ $supplier->phone_number ?? '-' }}</li>
                            <li class="flex items-start gap-2"><i class="bi bi-geo-alt text-indigo-400 mt-0.5"></i> {{ $supplier->address ?? '-' }}</li>
                        </ul>
                    </div>

                    {{-- Detail Bank --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Informasi Bank</h4>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-center gap-2"><span class="text-xs text-gray-400 w-12">NPWP:</span> {{ $supplier->npwp ?? '-' }}</li>
                            <li class="flex items-center gap-2"><span class="text-xs text-gray-400 w-12">Bank:</span> {{ $supplier->bank_name ?? '-' }}</li>
                            <li class="flex items-center gap-2"><span class="text-xs text-gray-400 w-12">Rek:</span> <span class="font-mono">{{ $supplier->account_number ?? '-' }}</span></li>
                        </ul>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex flex-col gap-2 justify-center">
                        @if($supplier->trashed())
                            <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore w-full">
                                @csrf @method('PATCH')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="w-full px-3 py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition flex items-center justify-center gap-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                </button>
                            </form>
                        @else
                            <div class="flex gap-2">
                                <a href="{{ route('suppliers.show', $supplier->supplier_id) }}" class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition text-center">
                                    Detail
                                </a>
                                <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-indigo-600 hover:bg-indigo-50 transition text-center">
                                    Edit
                                </a>
                            </div>
                            <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete w-full">
                                @csrf @method('DELETE')
                                <button type="submit" data-name="{{ $supplier->supplier_name }}" class="w-full px-3 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 transition flex items-center justify-center gap-2">
                                    <i class="bi bi-archive"></i> Arsipkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-shop text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Belum ada Supplier</h3>
            <p class="text-gray-500 text-sm mt-1">Mulai tambahkan data vendor atau pemasok Anda.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $suppliers->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Accordion Logic (Vanilla JS)
    function toggleAccordion(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if(el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if(icon) icon.classList.add('rotate-180');
        } else {
            el.classList.add('hidden');
            if(icon) icon.classList.remove('rotate-180');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // SweetAlert Konfirmasi
        const confirmAction = (formClass, title, text, btnText, btnColor) => {
            document.querySelectorAll(formClass).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const name = this.querySelector('button').dataset.name;
                    Swal.fire({
                        title: title,
                        text: text.replace(':name', name),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: btnText,
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) this.submit();
                    });
                });
            });
        };

        confirmAction('.form-delete', 'Arsipkan?', 'Anda akan mengarsipkan supplier ":name".', 'Ya, Arsipkan!', '#ef4444');
        confirmAction('.form-restore', 'Pulihkan?', 'Anda akan memulihkan supplier ":name".', 'Ya, Pulihkan!', '#10b981');
    });
</script>
@endpush