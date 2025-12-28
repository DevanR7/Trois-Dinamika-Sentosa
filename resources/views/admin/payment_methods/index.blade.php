@extends('admin.layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Metode Pembayaran</h1>
            <p class="page-subtitle">Atur metode pembayaran dan konfigurasi alur verifikasi.</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Link ke Arsip --}}
            <a href="{{ route('admin.payment-methods.archived.index') }}" class="btn btn-secondary text-slate-500">
                <i class="material-icons text-sm mr-1">archive</i> Arsip
            </a>
            
            <a href="{{ route('admin.payment-methods.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add</i> Tambah Metode
            </a>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th class="w-48">Nama Metode</th>
                        <th class="w-32 text-center">Tipe Proses</th>
                        
                        {{-- Kolom Konfigurasi --}}
                        <th>
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <span>Config Client</span>
                                <span>Config Internal</span>
                            </div>
                        </th>
                        
                        <th class="text-center w-24">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentMethods as $index => $method)
                        <tr>
                            <td class="text-center text-slate-500">{{ $index + 1 }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $method->name }}
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $typeLabel = match($method->type) {
                                        'direct' => 'Langsung',
                                        'pending' => 'Verifikasi',
                                        'gateway' => 'Gateway',
                                        default => $method->type
                                    };
                                    $typeClass = match($method->type) {
                                        'direct' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'gateway' => 'badge-primary',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $typeClass }} text-[10px]">{{ $typeLabel }}</span>
                            </td>
                            
                            {{-- KONFIGURASI --}}
                            <td class="p-0">
                                <div class="grid grid-cols-2 h-full divide-x divide-slate-100 dark:divide-slate-700">
                                    
                                    {{-- CLIENT --}}
                                    <div class="p-3 text-sm">
                                        <div class="flex flex-col gap-1">
                                            @php
                                                $clientReq = match($method->client_input_config) {
                                                    'none' => 'Tanpa Syarat',
                                                    'proof_only' => 'Wajib Bukti',
                                                    'reference_only' => 'Wajib Ref.',
                                                    'proof_and_reference' => 'Bukti & Ref.',
                                                };
                                                $clientStat = match($method->client_status_default) {
                                                    'completed' => 'Lunas',
                                                    'pending_verification' => 'Pending',
                                                };
                                                $clientStatClass = $method->client_status_default == 'completed' ? 'text-emerald-600' : 'text-amber-600';
                                            @endphp
                                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $clientReq }}</span>
                                            <span class="text-[10px] {{ $clientStatClass }} font-bold bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 rounded w-fit">
                                                -> {{ $clientStat }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- INTERNAL --}}
                                    <div class="p-3 text-sm bg-slate-50/50 dark:bg-slate-800/30">
                                        <div class="flex flex-col gap-1">
                                            @php
                                                $intReq = match($method->internal_input_config) {
                                                    'none' => 'Bebas',
                                                    'proof_only' => 'Wajib Bukti',
                                                    'reference_only' => 'Wajib Ref.',
                                                    'proof_and_reference' => 'Bukti & Ref.',
                                                };
                                                $intStat = match($method->internal_status_default) {
                                                    'completed' => 'Lunas',
                                                    'pending_verification' => 'Pending',
                                                };
                                                $intStatClass = $method->internal_status_default == 'completed' ? 'text-emerald-600' : 'text-amber-600';
                                            @endphp
                                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $intReq }}</span>
                                            <span class="text-[10px] {{ $intStatClass }} font-bold bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-1.5 py-0.5 rounded w-fit">
                                                -> {{ $intStat }}
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </td>

                            <td class="text-center">
                                @if($method->is_active)
                                    <span class="badge badge-success text-[10px]">Aktif</span>
                                @else
                                    <span class="badge badge-danger text-[10px]">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.payment-methods.edit', $method->payment_method_id) }}" 
                                       class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[16px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete Button (Soft Delete) --}}
                                    <button type="button" onclick="confirmDelete('{{ $method->payment_method_id }}', '{{ $method->name }}')" 
                                            class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Arsipkan">
                                        <i class="material-icons text-[16px] leading-none">archive</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $method->payment_method_id }}" 
                                          action="{{ route('admin.payment-methods.destroy', $method->payment_method_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">payments</i>
                                    <span>Belum ada metode pembayaran.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Arsipkan Metode?',
            text: "Metode '" + name + "' akan dinonaktifkan sementara (soft delete).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Arsipkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush