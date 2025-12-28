@extends('admin.layouts.app')

@section('title', 'Edit Metode Pembayaran')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Metode: {{ $paymentMethod->name }}</h1>
                <p class="page-subtitle">Perbarui konfigurasi pembayaran.</p>
            </div>
            <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.payment-methods.update', $paymentMethod->payment_method_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Umum</h3>
                </div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Nama Metode --}}
                    <div class="md:col-span-2">
                        <label class="form-label label-required">Nama Metode</label>
                        <input type="text" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               value="{{ old('name', $paymentMethod->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tipe Proses --}}
                    <div>
                        <label class="form-label label-required">Tipe Proses</label>
                        <select name="type" class="tom-select" required>
                            <option value="direct" {{ old('type', $paymentMethod->type) == 'direct' ? 'selected' : '' }}>Langsung (Direct/Cash)</option>
                            <option value="pending" {{ old('type', $paymentMethod->type) == 'pending' ? 'selected' : '' }}>Butuh Verifikasi (Transfer)</option>
                            <option value="gateway" {{ old('type', $paymentMethod->type) == 'gateway' ? 'selected' : '' }}>Otomatis (Gateway)</option>
                        </select>
                        <div class="form-hint mt-1 text-[10px] text-slate-500">
                            Menentukan alur dasar sistem. Gateway khusus untuk Midtrans/Xendit.
                        </div>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 h-[74px]">
                        <div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Status Aktif</span>
                            <p class="text-xs text-slate-500">Metode ini dapat dipilih saat transaksi.</p>
                        </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                            {{-- ✅ PERBAIKAN: Tambahkan Hidden Input ini --}}
                            <input type="hidden" name="is_active" value="0">
                            
                            {{-- Checkbox asli --}}
                            <input type="checkbox" name="is_active" value="1" 
                                   class="sr-only peer" 
                                   {{ old('is_active', $paymentMethod->is_active) ? 'checked' : '' }}>
                                   
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                </div>
            </div>

            {{-- KONFIGURASI TERPISAH --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- 1. CONFIG PORTAL CLIENT --}}
                <div class="card border-t-4 border-indigo-500 shadow-sm">
                    <div class="card-header bg-indigo-50/50 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-800">
                        <div class="flex items-center gap-2">
                            <i class="material-icons text-indigo-600">person</i>
                            <h3 class="font-bold text-indigo-900 dark:text-indigo-300 text-sm uppercase">Konfigurasi Portal Client</h3>
                        </div>
                        <p class="text-xs text-indigo-600/80 mt-1">Aturan saat Client melakukan pembayaran sendiri.</p>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label class="form-label label-required">Syarat Input Klien</label>
                            <select name="client_input_config" class="form-select">
                                <option value="none" {{ old('client_input_config', $paymentMethod->client_input_config) == 'none' ? 'selected' : '' }}>Langsung (Tanpa Syarat)</option>
                                <option value="proof_only" {{ old('client_input_config', $paymentMethod->client_input_config) == 'proof_only' ? 'selected' : '' }}>Wajib Upload Bukti</option>
                                <option value="reference_only" {{ old('client_input_config', $paymentMethod->client_input_config) == 'reference_only' ? 'selected' : '' }}>Wajib No. Referensi</option>
                                <option value="proof_and_reference" {{ old('client_input_config', $paymentMethod->client_input_config) == 'proof_and_reference' ? 'selected' : '' }}>Wajib Bukti & Referensi</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label label-required">Status Awal Transaksi</label>
                            <select name="client_status_default" class="form-select">
                                <option value="pending_verification" {{ old('client_status_default', $paymentMethod->client_status_default) == 'pending_verification' ? 'selected' : '' }}>Pending (Menunggu Verifikasi Admin)</option>
                                <option value="completed" {{ old('client_status_default', $paymentMethod->client_status_default) == 'completed' ? 'selected' : '' }}>Langsung Lunas (Otomatis)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 2. CONFIG PORTAL INTERNAL --}}
                <div class="card border-t-4 border-emerald-500 shadow-sm">
                    <div class="card-header bg-emerald-50/50 dark:bg-emerald-900/10 border-b border-emerald-100 dark:border-emerald-800">
                        <div class="flex items-center gap-2">
                            <i class="material-icons text-emerald-600">admin_panel_settings</i>
                            <h3 class="font-bold text-emerald-900 dark:text-emerald-300 text-sm uppercase">Konfigurasi Portal Internal</h3>
                        </div>
                        <p class="text-xs text-emerald-600/80 mt-1">Aturan saat Admin/Sales mencatat pembayaran.</p>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label class="form-label label-required">Syarat Input Staff</label>
                            <select name="internal_input_config" class="form-select">
                                <option value="none" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'none' ? 'selected' : '' }}>Bebas (Tanpa Syarat)</option>
                                <option value="proof_only" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'proof_only' ? 'selected' : '' }}>Wajib Upload Bukti</option>
                                <option value="reference_only" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'reference_only' ? 'selected' : '' }}>Wajib No. Referensi</option>
                                <option value="proof_and_reference" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'proof_and_reference' ? 'selected' : '' }}>Wajib Bukti & Referensi</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label label-required">Status Awal Transaksi</label>
                            <select name="internal_status_default" class="form-select">
                                <option value="completed" {{ old('internal_status_default', $paymentMethod->internal_status_default) == 'completed' ? 'selected' : '' }}>Langsung Lunas</option>
                                <option value="pending_verification" {{ old('internal_status_default', $paymentMethod->internal_status_default) == 'pending_verification' ? 'selected' : '' }}>Pending (Perlu Approval Lain)</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Submit & Archive --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">archive</i> Arsipkan
                </button>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        <form id="deleteForm" action="{{ route('admin.payment-methods.destroy', $paymentMethod->payment_method_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Arsipkan Metode?',
            text: "Metode ini akan disembunyikan dari pilihan pembayaran.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Arsipkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush