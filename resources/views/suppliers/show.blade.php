@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('suppliers.index') }}" class="hover:text-indigo-600 transition">Supplier</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $supplier->supplier_name }}</h2>
        </div>
        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KIRI: INFO --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-card-heading text-indigo-500"></i> Profil Lengkap
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Narahubung (PIC)</span>
                        <p class="text-sm font-semibold text-gray-900">{{ $supplier->person_in_charge ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Telepon</span>
                        <p class="text-sm font-semibold text-gray-900">{{ $supplier->phone_number ?? '-' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Alamat</span>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $supplier->address ?? '-' }}
                        </p>
                    </div>
                    
                    <div class="sm:col-span-2 border-t border-dashed border-gray-200 pt-4 mt-2">
                        <h4 class="text-xs font-bold text-gray-900 mb-3">Data Perbankan</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">NPWP</span>
                                <span class="text-sm text-gray-800 font-mono">{{ $supplier->npwp ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">Bank</span>
                                <span class="text-sm text-gray-800">{{ $supplier->bank_name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">No. Rekening</span>
                                <span class="text-sm text-gray-800 font-mono font-medium">{{ $supplier->account_number ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KANAN: SALDO & AKSI --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Card Saldo --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Saldo Deposit</span>
                <div class="text-3xl font-bold {{ $supplier->balance > 0 ? 'text-green-600' : 'text-gray-800' }} my-2">
                    Rp {{ number_format($supplier->balance ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-gray-400">Saldo mengendap saat ini.</p>
            </div>

            {{-- Card Aksi --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-xs font-bold text-gray-900 uppercase mb-4">Tindakan</h3>
                <div class="flex flex-col gap-3">
                    @if($supplier->trashed())
                        <form action="{{ route('suppliers.restore', $supplier->supplier_id) }}" method="POST" class="form-restore">
                            @csrf @method('PATCH')
                            <button type="submit" data-name="{{ $supplier->supplier_name }}" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition shadow-sm flex items-center justify-center gap-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Pulihkan Supplier
                            </button>
                        </form>
                    @else
                        <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-sm flex items-center justify-center gap-2">
                            <i class="bi bi-pencil-square"></i> Edit Data
                        </a>
                        <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="form-delete">
                            @csrf @method('DELETE')
                            <button type="submit" data-name="{{ $supplier->supplier_name }}" class="w-full px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm flex items-center justify-center gap-2">
                                <i class="bi bi-archive"></i> Arsipkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        confirmAction('.form-delete', 'Arsipkan?', 'Anda akan mengarsipkan ":name".', 'Ya, Arsipkan!', '#ef4444');
        confirmAction('.form-restore', 'Pulihkan?', 'Anda akan memulihkan ":name".', 'Ya, Pulihkan!', '#10b981');
    });
</script>
@endpush