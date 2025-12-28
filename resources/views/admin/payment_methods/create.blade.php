@extends('admin.layouts.app')

@section('title', 'Tambah Metode Pembayaran')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah Metode</h1>
                <p class="page-subtitle">Konfigurasi jenis pembayaran baru.</p>
            </div>
            <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.payment-methods.store') }}" method="POST">
            @csrf

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
                               placeholder="Contoh: Transfer BCA, Tunai, Cek/Giro" 
                               value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tipe Proses --}}
                    <div>
                        <label class="form-label label-required">Tipe Proses</label>
                        <select name="type" class="tom-select" required>
                            <option value="direct" {{ old('type') == 'direct' ? 'selected' : '' }}>Langsung (Direct/Cash)</option>
                            <option value="pending" {{ old('type') == 'pending' ? 'selected' : '' }}>Butuh Verifikasi (Transfer)</option>
                            <option value="gateway" {{ old('type') == 'gateway' ? 'selected' : '' }}>Otomatis (Gateway)</option>
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
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
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
                                <option value="none">Langsung (Tanpa Syarat)</option>
                                <option value="proof_only">Wajib Upload Bukti</option>
                                <option value="reference_only">Wajib No. Referensi</option>
                                <option value="proof_and_reference" selected>Wajib Bukti & Referensi</option>
                            </select>
                            <p class="form-hint">Apa yang harus diupload/diisi oleh klien?</p>
                        </div>
                        <div>
                            <label class="form-label label-required">Status Awal Transaksi</label>
                            <select name="client_status_default" class="form-select">
                                <option value="pending_verification" selected>Pending (Menunggu Verifikasi Admin)</option>
                                <option value="completed">Langsung Lunas (Otomatis)</option>
                            </select>
                            <p class="form-hint">Status default setelah klien submit bayar.</p>
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
                                <option value="none" selected>Bebas (Tanpa Syarat)</option>
                                <option value="proof_only">Wajib Upload Bukti</option>
                                <option value="reference_only">Wajib No. Referensi</option>
                                <option value="proof_and_reference">Wajib Bukti & Referensi</option>
                            </select>
                            <p class="form-hint">Apa yang wajib diisi oleh staff?</p>
                        </div>
                        <div>
                            <label class="form-label label-required">Status Awal Transaksi</label>
                            <select name="internal_status_default" class="form-select">
                                <option value="completed" selected>Langsung Lunas (Completed)</option>
                                <option value="pending_verification">Pending (Perlu Approval Lain)</option>
                            </select>
                            <p class="form-hint">Status default saat staff mencatat pembayaran.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Metode
                </button>
            </div>

        </form>
    </div>

@endsection