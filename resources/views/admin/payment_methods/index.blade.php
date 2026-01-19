@extends('admin.layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Metode Pembayaran</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Atur jalur pembayaran (Cash, Transfer, Gateway) dan aturan verifikasinya.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            {{-- Link ke Arsip --}}
            <a href="{{ route('admin.payment-methods.archived.index') }}" 
               class="btn bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-rose-600 transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:text-rose-400"
               title="Lihat Metode Non-Aktif/Dihapus">
                <i class="material-icons text-[18px]">inventory_2</i>
                <span class="hidden sm:inline">Arsip</span>
            </a>

            {{-- Tambah Baru --}}
            @can('manage-payment-methods')
                <a href="{{ route('admin.payment-methods.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px]">add</i>
                    <span>Metode Baru</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- 2. DATA TABLE --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Nama Metode & Tipe</th>
                        <th>Syarat Input (Klien / Admin)</th>
                        <th>Status Default</th>
                        <th class="text-center w-24">Aktif</th>
                        <th class="text-right sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/50 backdrop-blur-sm w-28 px-4">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentMethods as $index => $method)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-400 text-xs">
                                {{ $index + 1 }}
                            </td>

                            {{-- Nama & Tipe --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-sm">
                                        {{ $method->name }}
                                    </span>
                                    
                                    {{-- Badge Tipe --}}
                                    <div class="mt-1">
                                        @if($method->type == 'gateway')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                                <i class="material-icons text-[10px]">hub</i> Gateway (Auto)
                                            </span>
                                        @elseif($method->type == 'pending')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                                <i class="material-icons text-[10px]">hourglass_empty</i> Pending (Cek/Giro)
                                            </span>
                                        @else {{-- Direct --}}
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <i class="material-icons text-[10px]">payments</i> Langsung (Cash/TF)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Konfigurasi Input --}}
                            <td>
                                <div class="flex flex-col gap-2 text-xs">
                                    {{-- Client Config --}}
                                    <div class="flex items-center gap-2">
                                        <span class="w-14 text-slate-400 font-mono text-[10px] uppercase">Client:</span>
                                        @if($method->client_input_config == 'none')
                                            <span class="text-slate-400">-</span>
                                        @else
                                            <div class="flex gap-1">
                                                @if(in_array($method->client_input_config, ['proof_only', 'proof_and_reference']))
                                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded border border-blue-100 text-[10px] flex items-center gap-1" title="Wajib Upload Bukti">
                                                        <i class="material-icons text-[10px]">image</i> Bukti
                                                    </span>
                                                @endif
                                                @if(in_array($method->client_input_config, ['reference_only', 'proof_and_reference']))
                                                    <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded border border-indigo-100 text-[10px] flex items-center gap-1" title="Wajib Isi No. Ref">
                                                        <i class="material-icons text-[10px]">tag</i> Ref
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Admin Config --}}
                                    <div class="flex items-center gap-2">
                                        <span class="w-14 text-slate-400 font-mono text-[10px] uppercase">Admin:</span>
                                        @if($method->internal_input_config == 'none')
                                            <span class="text-slate-400">-</span>
                                        @else
                                            <div class="flex gap-1">
                                                @if(in_array($method->internal_input_config, ['proof_only', 'proof_and_reference']))
                                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded border border-slate-200 text-[10px] flex items-center gap-1">
                                                        <i class="material-icons text-[10px]">image</i> Bukti
                                                    </span>
                                                @endif
                                                @if(in_array($method->internal_input_config, ['reference_only', 'proof_and_reference']))
                                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded border border-slate-200 text-[10px] flex items-center gap-1">
                                                        <i class="material-icons text-[10px]">tag</i> Ref
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Status Default --}}
                            <td>
                                @if($method->type == 'gateway')
                                    <span class="text-xs text-purple-600 font-medium">Otomatis by System</span>
                                @else
                                    <div class="flex flex-col text-xs">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-slate-400">Client:</span>
                                            @if($method->client_status_default == 'completed')
                                                <span class="text-emerald-600 font-bold">Lunas</span>
                                            @else
                                                <span class="text-amber-600 font-bold">Verifikasi</span>
                                            @endif
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-slate-400">Admin:</span>
                                            @if($method->internal_status_default == 'completed')
                                                <span class="text-emerald-600 font-bold">Lunas</span>
                                            @else
                                                <span class="text-amber-600 font-bold">Verifikasi</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Aktif --}}
                            <td class="text-center">
                                @if($method->is_active)
                                    <i class="material-icons text-emerald-500" title="Aktif">check_circle</i>
                                @else
                                    <i class="material-icons text-slate-300" title="Non-Aktif">cancel</i>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right sticky right-0 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors z-10 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    @can('manage-payment-methods')
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.payment-methods.edit', $method->payment_method_id) }}" class="btn-action btn-action-edit" title="Edit Konfigurasi">
                                            <i class="material-icons">edit</i>
                                        </a>

                                        {{-- Delete (Arsip) --}}
                                        <form action="{{ route('admin.payment-methods.destroy', $method->payment_method_id) }}" method="POST" class="inline-block m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" 
                                                    class="btn-action btn-action-delete" 
                                                    title="Arsipkan Metode"
                                                    onclick="handleAction(this, 'Arsipkan Metode?', 'Metode ini tidak akan muncul lagi di pilihan pembayaran.', 'warning')">
                                                <i class="material-icons">archive</i>
                                            </button>
                                        </form>
                                    @endcan

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <i class="material-icons text-4xl text-slate-300 mb-2">payments</i>
                                    <p class="text-slate-500 text-sm">Belum ada metode pembayaran.</p>
                                    @can('manage-payment-methods')
                                        <a href="{{ route('admin.payment-methods.create') }}" class="mt-2 text-indigo-600 hover:underline text-sm">
                                            Buat Metode Baru
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Script Handle Action --}}
    @push('scripts')
    <script>
        function handleAction(button, title, text, type) {
            event.preventDefault();
            const form = button.closest('form');
            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: title,
                    text: text,
                    icon: type === 'danger' ? 'error' : type,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: type
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            } else {
                if(confirm(text)) form.submit();
            }
        }
    </script>
    @endpush

@endsection