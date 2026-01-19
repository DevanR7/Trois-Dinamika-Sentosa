@extends('admin.layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
    {{-- Main Content with Alpine Data --}}
    {{-- isLocked: true (Default terkunci agar aman) --}}
    <div x-data="{ activeTab: 'company', isLocked: true }">

        {{-- Header --}}
        <div class="page-header flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="page-title">Pengaturan Sistem</h1>
                <p class="page-subtitle">Konfigurasi profil perusahaan dan pemetaan akun akuntansi default (COA)</p>
            </div>

            {{-- TOGGLE LOCK BUTTON --}}
            <div class="flex items-center gap-3">
                {{-- Status Badge --}}
                <span class="px-3 py-1 rounded-full text-xs font-bold border transition-colors duration-300"
                      :class="isLocked 
                        ? 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700' 
                        : 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 animate-pulse'">
                    <i class="material-icons text-[14px] align-middle mr-1" x-text="isLocked ? 'lock' : 'lock_open'"></i>
                    <span x-text="isLocked ? 'Mode Baca (Terkunci)' : 'Mode Edit (Terbuka)'"></span>
                </span>

                {{-- Button Switch --}}
                <button type="button" 
                        @click="isLocked = !isLocked"
                        class="btn transition-all duration-300"
                        :class="isLocked ? 'btn-secondary' : 'btn-danger-solid'"
                        :title="isLocked ? 'Klik untuk mengedit data' : 'Klik untuk mengunci kembali'">
                    <i class="material-icons text-[18px]" x-text="isLocked ? 'edit' : 'lock'"></i>
                    <span x-text="isLocked ? 'Ubah Pengaturan' : 'Kunci Pengaturan'"></span>
                </button>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            
            {{-- Tabs Navigation --}}
            <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="me-2">
                        <button type="button" 
                                @click="activeTab = 'company'"
                                :class="activeTab === 'company' 
                                    ? 'inline-flex items-center justify-center p-4 text-indigo-600 border-b-2 border-indigo-600 rounded-t-lg active dark:text-indigo-500 dark:border-indigo-500 group' 
                                    : 'inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-slate-600 hover:border-slate-300 dark:hover:text-slate-300 group transition-all'">
                            <i class="material-icons text-[20px] mr-2" 
                               :class="activeTab === 'company' ? 'text-indigo-600 dark:text-indigo-500' : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300'">
                                business
                            </i>
                            Informasi Perusahaan
                        </button>
                    </li>
                    <li class="me-2">
                        <button type="button" 
                                @click="activeTab = 'accounting'"
                                :class="activeTab === 'accounting' 
                                    ? 'inline-flex items-center justify-center p-4 text-indigo-600 border-b-2 border-indigo-600 rounded-t-lg active dark:text-indigo-500 dark:border-indigo-500 group' 
                                    : 'inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-slate-600 hover:border-slate-300 dark:hover:text-slate-300 group transition-all'">
                            <i class="material-icons text-[20px] mr-2" 
                               :class="activeTab === 'accounting' ? 'text-indigo-600 dark:text-indigo-500' : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300'">
                                account_balance
                            </i>
                            Akun Default (COA)
                        </button>
                    </li>
                </ul>
            </div>

            {{-- FIELDSET WRAPPER: Kunci Utama Fitur Lock --}}
            {{-- Saat isLocked true, semua input di dalam fieldset ini otomatis disabled --}}
            <fieldset :disabled="isLocked" class="contents">

                {{-- TAB 1: INFORMASI PERUSAHAAN --}}
                <div x-show="activeTab === 'company'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {{-- Card Kiri: Identitas Utama --}}
                    <div class="lg:col-span-2">
                        <div class="card h-full">
                            <div class="card-header">
                                <h3 class="card-header-title">Identitas Perusahaan</h3>
                            </div>
                            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
                                
                                {{-- Nama Perusahaan --}}
                                <div class="form-group md:col-span-2">
                                    <label class="form-label label-required">Nama Perusahaan</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="material-icons text-slate-400 text-[18px]">store</i>
                                        </div>
                                        <input type="text" name="company_name" class="form-input pl-10 disabled:bg-slate-50 disabled:text-slate-500" 
                                               value="{{ old('company_name', $settings['company_name'] ?? '') }}" required>
                                    </div>
                                </div>

                                {{-- Pemilik --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Nama Pemilik / Direktur</label>
                                    <input type="text" name="company_owner" class="form-input disabled:bg-slate-50 disabled:text-slate-500" 
                                           value="{{ old('company_owner', $settings['company_owner'] ?? '') }}" required>
                                </div>

                                {{-- NPWP --}}
                                <div class="form-group">
                                    <label class="form-label">NPWP Perusahaan</label>
                                    <input type="text" name="company_npwp" class="form-input disabled:bg-slate-50 disabled:text-slate-500" 
                                           placeholder="XX.XXX.XXX.X-XXX.XXX"
                                           value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}">
                                </div>

                                {{-- Telepon --}}
                                <div class="form-group">
                                    <label class="form-label">Nomor Telepon</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="material-icons text-slate-400 text-[18px]">phone</i>
                                        </div>
                                        <input type="text" name="company_phone" class="form-input pl-10 disabled:bg-slate-50 disabled:text-slate-500" 
                                               placeholder="0812..."
                                               value="{{ old('company_phone', $settings['company_phone'] ?? '') }}">
                                    </div>
                                </div>

                                {{-- Kota / Provinsi --}}
                                <div class="form-group">
                                    <label class="form-label">Kota & Provinsi</label>
                                    <input type="text" name="company_city_province" class="form-input disabled:bg-slate-50 disabled:text-slate-500" 
                                           placeholder="Jakarta Selatan, DKI Jakarta"
                                           value="{{ old('company_city_province', $settings['company_city_province'] ?? '') }}">
                                </div>

                                {{-- Alamat Lengkap --}}
                                <div class="form-group md:col-span-2">
                                    <label class="form-label">Alamat Lengkap</label>
                                    <textarea name="company_address" class="form-textarea disabled:bg-slate-50 disabled:text-slate-500" rows="3">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Card Kanan: Versi Sistem & Lainnya --}}
                    <div class="lg:col-span-1">
                        <div class="card h-full">
                            <div class="card-header">
                                <h3 class="card-header-title">Info Sistem</h3>
                            </div>
                            <div class="card-body space-y-4">
                                
                                <div class="form-group">
                                    <label class="form-label">Versi Aplikasi</label>
                                    <input type="text" name="system_version" 
                                           class="form-input disabled:bg-slate-50 disabled:text-slate-500" 
                                           value="{{ old('system_version', $settings['system_version'] ?? '1.0.0') }}" 
                                           placeholder="Contoh: 1.0.2">
                                    <p class="text-xs text-slate-400 mt-1">Ubah manual nomor versi sistem jika diperlukan.</p>
                                </div>
                                
                                <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                                    <div class="flex gap-3">
                                        <i class="material-icons text-indigo-500">info</i>
                                        <div>
                                            <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300">Catatan</h4>
                                            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 leading-relaxed">
                                                Pastikan tombol "Kunci Pengaturan" aktif setelah melakukan perubahan untuk mencegah ketidaksengajaan.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: AKUN DEFAULT (COA) --}}
                <div x-show="activeTab === 'accounting'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     style="display: none;">
                    
                    {{-- Alert Info --}}
                    <div class="p-4 mb-6 text-sm text-amber-800 rounded-lg bg-amber-50 dark:bg-slate-800 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 flex items-start gap-3" role="alert">
                        <i class="material-icons text-[20px]">warning_amber</i>
                        <div>
                            <span class="font-bold">Perhatian Penting:</span>
                            Mengubah akun default dapat mempengaruhi alur penjurnalan otomatis untuk transaksi masa depan. Transaksi masa lalu tidak akan berubah.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        {{-- 1. GROUP PENJUALAN & PIUTANG --}}
                        <div class="card h-full">
                            <div class="card-header border-l-4 border-l-emerald-500">
                                <h3 class="card-header-title">Penjualan & Piutang (Sales)</h3>
                            </div>
                            <div class="card-body space-y-5">
                                
                                {{-- Akun Piutang (AR) --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Piutang Usaha (AR)</label>
                                    <select name="acct_default_ar" class="tom-select disabled:cursor-not-allowed">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_ar'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Digunakan saat Invoice dibuat (Debit AR).</p>
                                </div>

                                {{-- Pendapatan Penjualan --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Pendapatan Penjualan</label>
                                    <select name="acct_default_sales_revenue" class="tom-select">
                                        <option value="">Pilih Akun Pendapatan...</option>
                                        @foreach($revenueAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_sales_revenue'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Kredit saat Invoice dikonfirmasi.</p>
                                </div>

                                {{-- Retur Penjualan --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Retur Penjualan</label>
                                    <select name="acct_default_sales_return" class="tom-select">
                                        <option value="">Pilih Akun (Biasanya Contra-Revenue)...</option>
                                        @foreach($expenseOrRevenueAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_sales_return'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Debit saat terjadi Sales Return.</p>
                                </div>

                                {{-- Deposit Klien --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Deposit Klien (Uang Muka Penjualan)</label>
                                    <select name="acct_default_client_deposit" class="tom-select">
                                        <option value="">Pilih Akun Liabilitas...</option>
                                        @foreach($liabilityAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_client_deposit'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Menampung kelebihan bayar pelanggan.</p>
                                </div>

                                {{-- Gateway Default --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Gateway Pembayaran Default</label>
                                    <select name="acct_default_gateway" class="tom-select">
                                        <option value="">Pilih Akun Kas/Bank...</option>
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_gateway'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Digunakan untuk penerimaan Midtrans/Online otomatis.</p>
                                </div>

                            </div>
                        </div>

                        {{-- 2. GROUP PEMBELIAN & PERSEDIAAN --}}
                        <div class="card h-full">
                            <div class="card-header border-l-4 border-l-rose-500">
                                <h3 class="card-header-title">Pembelian & Persediaan (Purchase)</h3>
                            </div>
                            <div class="card-body space-y-5">
                                
                                {{-- Persediaan --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Persediaan Barang (Inventory)</label>
                                    <select name="acct_default_inventory" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_inventory'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Debit saat Beli, Kredit saat Jual (COGS).</p>
                                </div>

                                {{-- HPP --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Harga Pokok Penjualan (COGS)</label>
                                    <select name="acct_default_cogs" class="tom-select">
                                        <option value="">Pilih Akun HPP...</option>
                                        @foreach($cogsAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_cogs'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Debit saat barang terjual.</p>
                                </div>

                                {{-- Hutang Dagang --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Hutang Usaha (AP)</label>
                                    <select name="acct_default_ap" class="tom-select">
                                        <option value="">Pilih Akun Liabilitas...</option>
                                        @foreach($liabilityAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_ap'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Kredit saat terima barang (PO).</p>
                                </div>

                                {{-- Retur Pembelian --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Retur Pembelian</label>
                                    <select name="acct_default_purchase_return" class="tom-select">
                                        <option value="">Pilih Akun (Biasanya Kredit Inventory)...</option>
                                        @foreach($assetOrLiabilityAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_purchase_return'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Biasanya diarahkan ke Akun Inventory (Mengurangi nilai stok).</p>
                                </div>

                                {{-- Deposit Supplier --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Deposit ke Supplier (Uang Muka Pembelian)</label>
                                    <select name="acct_default_supplier_deposit" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_supplier_deposit'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Aset lancar untuk menampung kelebihan bayar ke supplier.</p>
                                </div>

                            </div>
                        </div>

                        {{-- 3. GROUP LAINNYA --}}
                        <div class="card lg:col-span-2">
                            <div class="card-header border-l-4 border-l-slate-500">
                                <h3 class="card-header-title">Lainnya (Adjustment & Equity)</h3>
                            </div>
                            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
                                
                                {{-- Inventory Adjustment --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Penyesuaian Stok (Stock Opname)</label>
                                    <select name="acct_default_inventory_adjustment" class="tom-select">
                                        <option value="">Pilih Akun Beban/Pendapatan...</option>
                                        @foreach($expenseOrRevenueAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_inventory_adjustment'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Menampung selisih stok (Gain/Loss).</p>
                                </div>

                                {{-- Laba Ditahan --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Akun Laba Ditahan (Retained Earnings)</label>
                                    <select name="acct_default_retained_earnings" class="tom-select">
                                        <option value="">Pilih Akun Ekuitas...</option>
                                        @foreach($equityAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ ($settings['acct_default_retained_earnings'] ?? '') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Tujuan akhir Tutup Buku Tahunan.</p>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </fieldset>

            {{-- FOOTER ACTION --}}
            {{-- Tombol Simpan hanya muncul jika Lock TERBUKA --}}
            <div x-show="!isLocked" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-10 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 class="fixed bottom-6 right-6 z-40">
                <button type="submit" class="btn btn-primary btn-lg shadow-xl hover:scale-105 transition-transform flex items-center gap-2">
                    <i class="material-icons">save</i>
                    Simpan Semua Pengaturan
                </button>
            </div>

        </form>
    </div>
@endsection