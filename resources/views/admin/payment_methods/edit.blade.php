@extends('admin.layouts.app')

@section('title', 'Edit Metode Pembayaran')

@section('content')

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Edit Metode</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Ubah konfigurasi untuk <span class="font-bold text-indigo-600">{{ $paymentMethod->name }}</span>.
            </p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Kembali</span>
        </a>
    </div>

    {{-- ERROR ALERT (PENTING: Agar terlihat jika ada validasi gagal) --}}
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-900/20 dark:border-rose-800">
            <div class="flex items-start gap-3">
                <i class="material-icons text-rose-600 mt-0.5">error</i>
                <div>
                    <h4 class="text-sm font-bold text-rose-700 dark:text-rose-400 mb-1">Gagal Menyimpan!</h4>
                    <ul class="list-disc list-inside text-xs text-rose-600 dark:text-rose-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.payment-methods.update', $paymentMethod->payment_method_id) }}" 
          method="POST" 
          x-data="paymentMethodEditForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI (8 Kolom - Desain Lama) --}}
            <div class="xl:col-span-8 space-y-6">
                
                {{-- Card: Identitas --}}
                <div class="card p-6">
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-5">
                        <h3 class="card-header-title">Informasi Dasar</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        {{-- Nama Metode --}}
                        <div>
                            <label class="form-label label-required">Nama Metode</label>
                            <input type="text" name="name" 
                                   class="form-input @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $paymentMethod->name) }}" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Tipe (Dropdown Biasa Sesuai Desain Lama) --}}
                            <div>
                                <label class="form-label label-required">Tipe Pembayaran</label>
                                <select name="type" x-model="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="direct">Langsung (Direct)</option>
                                    <option value="pending">Tertunda (Pending/Tempo)</option>
                                    <option value="gateway">Payment Gateway (Otomatis)</option>
                                </select>
                            </div>

                            {{-- Deskripsi --}}
                            <div>
                                <label class="form-label">Deskripsi Singkat</label>
                                <input type="text" name="description" class="form-input" 
                                       value="{{ old('description', $paymentMethod->description) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Konfigurasi Manual (Logic AlpineJS) --}}
                <div class="card p-6" x-show="type !== 'gateway'" x-transition>
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-5">
                        <h3 class="card-header-title flex items-center gap-2">
                            <i class="material-icons text-indigo-500">settings_applications</i> Konfigurasi Input
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {{-- Klien --}}
                        <div class="space-y-4 border-r border-slate-100 dark:border-slate-700 pr-4">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Untuk Klien (Portal)</h4>
                            
                            <div>
                                <label class="form-label">Syarat Input</label>
                                <select name="client_input_config" class="form-select text-sm">
                                    <option value="none" {{ old('client_input_config', $paymentMethod->client_input_config) == 'none' ? 'selected' : '' }}>Tidak Ada Syarat</option>
                                    <option value="proof_only" {{ old('client_input_config', $paymentMethod->client_input_config) == 'proof_only' ? 'selected' : '' }}>Wajib Upload Bukti</option>
                                    <option value="reference_only" {{ old('client_input_config', $paymentMethod->client_input_config) == 'reference_only' ? 'selected' : '' }}>Wajib Isi No. Ref</option>
                                    <option value="proof_and_reference" {{ old('client_input_config', $paymentMethod->client_input_config) == 'proof_and_reference' ? 'selected' : '' }}>Wajib Bukti & No. Ref</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Status Awal</label>
                                <select name="client_status_default" class="form-select text-sm">
                                    {{-- FIX: Value harus 'pending_verification', BUKAN 'pending' --}}
                                    <option value="pending_verification" {{ old('client_status_default', $paymentMethod->client_status_default) == 'pending_verification' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                    <option value="completed" {{ old('client_status_default', $paymentMethod->client_status_default) == 'completed' ? 'selected' : '' }}>Langsung Lunas (Completed)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Admin --}}
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Untuk Admin (Internal)</h4>
                            
                            <div>
                                <label class="form-label">Syarat Input</label>
                                <select name="internal_input_config" class="form-select text-sm">
                                    <option value="none" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'none' ? 'selected' : '' }}>Tidak Ada Syarat</option>
                                    <option value="proof_only" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'proof_only' ? 'selected' : '' }}>Wajib Upload Bukti</option>
                                    <option value="reference_only" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'reference_only' ? 'selected' : '' }}>Wajib Isi No. Ref</option>
                                    <option value="proof_and_reference" {{ old('internal_input_config', $paymentMethod->internal_input_config) == 'proof_and_reference' ? 'selected' : '' }}>Wajib Bukti & No. Ref</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Status Awal</label>
                                <select name="internal_status_default" class="form-select text-sm">
                                    {{-- FIX: Value harus 'pending_verification' --}}
                                    <option value="pending_verification" {{ old('internal_status_default', $paymentMethod->internal_status_default) == 'pending_verification' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                    <option value="completed" {{ old('internal_status_default', $paymentMethod->internal_status_default) == 'completed' ? 'selected' : '' }}>Langsung Lunas (Completed)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Alert Gateway --}}
                <div class="p-4 bg-purple-50 border border-purple-100 rounded-xl flex items-center gap-3" x-show="type === 'gateway'" x-transition style="display: none;">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                        <i class="material-icons">hub</i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-purple-800">Mode Otomatis</h4>
                        <p class="text-xs text-purple-600 mt-1">
                            Status pembayaran dikelola otomatis oleh Gateway.
                        </p>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (4 Kolom - Desain Lama) --}}
            <div class="xl:col-span-4 space-y-6">
                
                <div class="card p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pengaturan</h3>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block">Status Aktif</label>
                            <span class="text-[10px] text-slate-500">Tampilkan metode ini?</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            {{-- FIX: Hidden Input is_active agar nilai 0 terkirim jika uncheck --}}
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" 
                                {{ old('is_active', $paymentMethod->is_active) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3 group">
                        <i class="material-icons text-[18px]">save</i>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary w-full justify-center">
                        Batal
                    </a>
                </div>

            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function paymentMethodEditForm() {
            return {
                type: @json(old('type', $paymentMethod->type))
            }
        }
    </script>
    @endpush

@endsection