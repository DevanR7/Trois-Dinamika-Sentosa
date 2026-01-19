@extends('admin.layouts.app')

@section('title', 'Tambah Metode Pembayaran')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Metode Pembayaran Baru</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Tambahkan opsi pembayaran baru untuk transaksi.
            </p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Batal</span>
        </a>
    </div>

    <form action="{{ route('admin.payment-methods.store') }}" method="POST" x-data="paymentMethodForm()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI (8 Kolom) --}}
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
                                   placeholder="Contoh: Bank BCA, Tunai, Cek Giro" 
                                   value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Tipe --}}
                            <div>
                                <label class="form-label label-required">Tipe Pembayaran</label>
                                <select name="type" x-model="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="direct">Langsung (Direct)</option>
                                    <option value="pending">Tertunda (Pending/Tempo)</option>
                                    <option value="gateway">Payment Gateway (Otomatis)</option>
                                </select>
                                <p class="text-[10px] text-slate-500 mt-1" x-text="typeHint"></p>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Keterangan --}}
                            <div>
                                <label class="form-label">Deskripsi Singkat</label>
                                <input type="text" name="description" class="form-input" 
                                       placeholder="Ket. untuk user (No Rek, Atas Nama, dll)" 
                                       value="{{ old('description') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Konfigurasi Manual (Hidden if Gateway) --}}
                <div class="card p-6" x-show="type !== 'gateway'" x-transition>
                    <div class="card-header bg-transparent px-0 pt-0 pb-3 border-b border-slate-100 dark:border-slate-700 mb-5">
                        <h3 class="card-header-title flex items-center gap-2">
                            <i class="material-icons text-indigo-500">settings_applications</i> Konfigurasi Input
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {{-- Aturan Klien --}}
                        <div class="space-y-4 border-r border-slate-100 dark:border-slate-700 pr-4">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Untuk Klien (Portal)</h4>
                            
                            <div>
                                <label class="form-label">Syarat Input</label>
                                <select name="client_input_config" class="form-select text-sm">
                                    <option value="none">Tidak Ada Syarat</option>
                                    <option value="proof_only">Wajib Upload Bukti</option>
                                    <option value="reference_only">Wajib Isi No. Ref</option>
                                    <option value="proof_and_reference" selected>Wajib Bukti & No. Ref</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Status Awal</label>
                                <select name="client_status_default" class="form-select text-sm">
                                    <option value="pending" selected>Menunggu Verifikasi</option>
                                    <option value="completed">Langsung Lunas (Completed)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Aturan Admin --}}
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Untuk Admin (Internal)</h4>
                            
                            <div>
                                <label class="form-label">Syarat Input</label>
                                <select name="internal_input_config" class="form-select text-sm">
                                    <option value="none" selected>Tidak Ada Syarat</option>
                                    <option value="proof_only">Wajib Upload Bukti</option>
                                    <option value="reference_only">Wajib Isi No. Ref</option>
                                    <option value="proof_and_reference">Wajib Bukti & No. Ref</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Status Awal</label>
                                <select name="internal_status_default" class="form-select text-sm">
                                    <option value="pending">Menunggu Verifikasi</option>
                                    <option value="completed" selected>Langsung Lunas (Completed)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    
                    <div class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg flex items-start gap-2">
                        <i class="material-icons text-indigo-600 text-sm mt-0.5">info</i>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed">
                            <strong>Tips:</strong> Untuk Transfer Bank, biasanya Klien wajib upload bukti & status "Pending". Admin mungkin tidak wajib bukti & status "Lunas" (karena sudah cek mutasi).
                        </p>
                    </div>
                </div>

                {{-- Alert jika Gateway --}}
                <div class="p-4 bg-purple-50 border border-purple-100 rounded-xl flex items-center gap-3" x-show="type === 'gateway'" x-transition style="display: none;">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                        <i class="material-icons">hub</i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-purple-800">Mode Otomatis</h4>
                        <p class="text-xs text-purple-600 mt-1">
                            Status pembayaran dan verifikasi akan ditangani otomatis oleh penyedia Gateway (Midtrans/Xendit) melalui callback system.
                        </p>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (4 Kolom) --}}
            <div class="xl:col-span-4 space-y-6">
                
                {{-- Card Status --}}
                <div class="card p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pengaturan</h3>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block">Status Aktif</label>
                            <span class="text-[10px] text-slate-500">Tampilkan metode ini?</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                {{-- Action --}}
                <div class="card p-5 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Aksi</h3>
                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/30 mb-3">
                        <i class="material-icons text-[18px]">save</i>
                        Simpan Metode
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
        function paymentMethodForm() {
            return {
                type: 'direct',
                get typeHint() {
                    switch(this.type) {
                        case 'direct': return 'Contoh: Transfer Bank, Tunai (Cash).';
                        case 'pending': return 'Contoh: Cek, Giro, atau Tempo (Bon).';
                        case 'gateway': return 'Contoh: Midtrans, Xendit, Stripe.';
                        default: return '';
                    }
                }
            }
        }
    </script>
    @endpush

@endsection