@extends('admin.layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')

    <div class="max-w-6xl mx-auto">

        {{-- HEADER --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Pengaturan Sistem</h1>
                <p class="page-subtitle">Konfigurasi identitas perusahaan dan pemetaan akun akuntansi (COA).</p>
            </div>
        </div>

        {{-- MAIN FORM & TABS --}}
        <div x-data="{ activeTab: 'general' }">
            
            {{-- Tab Navigation --}}
            <div class="flex flex-wrap gap-2 mb-6">
                <button @click="activeTab = 'general'" 
                        :class="activeTab === 'general' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 flex items-center gap-2">
                    <i class="material-icons text-sm">business</i> Profil Perusahaan
                </button>
                <button @click="activeTab = 'accounting'" 
                        :class="activeTab === 'accounting' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 flex items-center gap-2">
                    <i class="material-icons text-sm">account_balance</i> Akun Default (Akuntansi)
                </button>
            </div>

            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                
                {{-- TAB 1: GENERAL SETTINGS --}}
                <div x-show="activeTab === 'general'" x-transition.opacity class="space-y-6">
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-header-title">Identitas Perusahaan</h3>
                        </div>
                        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Nama Perusahaan --}}
                            <div class="md:col-span-2">
                                <label class="form-label label-required">Nama Perusahaan</label>
                                <input type="text" name="company_name" 
                                       class="form-input font-bold text-lg" 
                                       value="{{ $settings['company_name'] ?? '' }}" required>
                            </div>

                            {{-- Pemilik --}}
                            <div>
                                <label class="form-label label-required">Nama Pemilik / Direktur</label>
                                <input type="text" name="company_owner" 
                                       class="form-input" 
                                       value="{{ $settings['company_owner'] ?? '' }}" required>
                            </div>

                            {{-- NPWP --}}
                            <div>
                                <label class="form-label label-optional">NPWP Perusahaan</label>
                                <input type="text" name="company_npwp" 
                                       class="form-input" 
                                       value="{{ $settings['company_npwp'] ?? '' }}">
                            </div>

                            {{-- Telepon --}}
                            <div>
                                <label class="form-label label-optional">No. Telepon / Kantor</label>
                                <input type="text" name="company_phone" 
                                       class="form-input" 
                                       value="{{ $settings['company_phone'] ?? '' }}">
                            </div>

                            {{-- Kota/Provinsi --}}
                            <div>
                                <label class="form-label label-optional">Kota & Provinsi</label>
                                <input type="text" name="company_city_province" 
                                       class="form-input" 
                                       placeholder="Contoh: Semarang, Jawa Tengah"
                                       value="{{ $settings['company_city_province'] ?? '' }}">
                            </div>

                            {{-- Alamat --}}
                            <div class="md:col-span-2">
                                <label class="form-label label-optional">Alamat Lengkap</label>
                                <textarea name="company_address" class="form-textarea" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-slate-700 dark:text-slate-200">Versi Sistem</h4>
                                <p class="text-xs text-slate-500">Informasi versi aplikasi untuk keperluan maintenance.</p>
                            </div>
                            <div class="w-32">
                                <input type="text" name="system_version" class="form-input text-center font-mono text-xs" value="{{ $settings['system_version'] ?? '1.0.0' }}">
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TAB 2: ACCOUNTING MAPPING --}}
                <div x-show="activeTab === 'accounting'" x-transition.opacity style="display: none;">
                    
                    <div class="alert alert-info bg-indigo-50 border-indigo-100 text-indigo-800 p-4 rounded-xl mb-6 flex items-start gap-3">
                        <i class="material-icons text-indigo-500 mt-0.5">info</i>
                        <div class="text-sm leading-relaxed">
                            <strong>Penting:</strong> Pengaturan ini menentukan ke mana jurnal otomatis akan diposting. 
                            Pastikan akun yang dipilih sesuai dengan Chart of Accounts (COA) Anda agar laporan keuangan akurat.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- GROUP 1: PENJUALAN & PIUTANG --}}
                        <div class="card h-fit">
                            <div class="card-header bg-emerald-50/50 dark:bg-emerald-900/10 border-b border-emerald-100 dark:border-emerald-800">
                                <h3 class="card-header-title text-emerald-800 dark:text-emerald-400">Penjualan & Piutang</h3>
                            </div>
                            <div class="card-body space-y-5">
                                
                                {{-- AR --}}
                                <div>
                                    <label class="form-label label-required">Akun Piutang Usaha (AR)</label>
                                    <select name="acct_default_ar" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_ar'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Debit saat Invoice dibuat.</div>
                                </div>

                                {{-- Revenue --}}
                                <div>
                                    <label class="form-label label-required">Akun Pendapatan Penjualan</label>
                                    <select name="acct_default_sales_revenue" class="tom-select">
                                        <option value="">Pilih Akun Pendapatan...</option>
                                        @foreach($revenueAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_sales_revenue'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Kredit saat Invoice dibuat.</div>
                                </div>

                                {{-- Sales Return --}}
                                <div>
                                    <label class="form-label label-required">Akun Retur Penjualan</label>
                                    <select name="acct_default_sales_return" class="tom-select">
                                        <option value="">Pilih Akun (Kontra Pendapatan)...</option>
                                        @foreach($expenseOrRevenueAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_sales_return'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Debit saat terjadi Retur Penjualan.</div>
                                </div>

                                {{-- Client Deposit --}}
                                <div>
                                    <label class="form-label label-required">Akun Deposit Pelanggan (Uang Muka)</label>
                                    <select name="acct_default_client_deposit" class="tom-select">
                                        <option value="">Pilih Akun Liabilitas...</option>
                                        @foreach($assetOrLiabilityAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_client_deposit'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Menampung kelebihan bayar pelanggan.</div>
                                </div>

                            </div>
                        </div>

                        {{-- GROUP 2: PEMBELIAN & HUTANG --}}
                        <div class="card h-fit">
                            <div class="card-header bg-rose-50/50 dark:bg-rose-900/10 border-b border-rose-100 dark:border-rose-800">
                                <h3 class="card-header-title text-rose-800 dark:text-rose-400">Pembelian & Hutang</h3>
                            </div>
                            <div class="card-body space-y-5">
                                
                                {{-- AP --}}
                                <div>
                                    <label class="form-label label-required">Akun Hutang Dagang (AP)</label>
                                    <select name="acct_default_ap" class="tom-select">
                                        <option value="">Pilih Akun Liabilitas...</option>
                                        @foreach($liabilityAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_ap'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Kredit saat Terima Barang (PO).</div>
                                </div>

                                {{-- Inventory --}}
                                <div>
                                    <label class="form-label label-required">Akun Persediaan (Inventory)</label>
                                    <select name="acct_default_inventory" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_inventory'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Debit saat Beli, Kredit saat Jual.</div>
                                </div>

                                {{-- COGS --}}
                                <div>
                                    <label class="form-label label-required">Akun HPP (COGS)</label>
                                    <select name="acct_default_cogs" class="tom-select">
                                        <option value="">Pilih Akun HPP...</option>
                                        @foreach($cogsAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_cogs'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Debit saat Invoice dikonfirmasi.</div>
                                </div>

                                {{-- Supplier Deposit --}}
                                <div>
                                    <label class="form-label label-required">Akun Deposit ke Supplier (Uang Muka)</label>
                                    <select name="acct_default_supplier_deposit" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_supplier_deposit'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Aset lancar untuk uang muka pembelian.</div>
                                </div>

                            </div>
                        </div>

                        {{-- GROUP 3: LAINNYA --}}
                        <div class="card lg:col-span-2 h-fit">
                            <div class="card-header bg-slate-50 dark:bg-slate-800">
                                <h3 class="card-header-title">Pengaturan Lainnya</h3>
                            </div>
                            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">

                                {{-- Gateway --}}
                                <div>
                                    <label class="form-label label-required">Akun Perantara Gateway (Midtrans)</label>
                                    <select name="acct_default_gateway" class="tom-select">
                                        <option value="">Pilih Akun Aset...</option>
                                        @foreach($assetAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_gateway'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Akun penampung sementara sebelum settlement.</div>
                                </div>

                                {{-- Inventory Adjustment --}}
                                <div>
                                    <label class="form-label label-required">Akun Penyesuaian Stok (Opname)</label>
                                    <select name="acct_default_inventory_adjustment" class="tom-select">
                                        <option value="">Pilih Akun Beban/HPP...</option>
                                        @foreach($expenseOrRevenueAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_inventory_adjustment'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Menampung selisih nilai saat Stock Opname.</div>
                                </div>

                                {{-- Purchase Return --}}
                                <div>
                                    <label class="form-label label-required">Akun Retur Pembelian</label>
                                    <select name="acct_default_purchase_return" class="tom-select">
                                        <option value="">Pilih Akun Aset/HPP...</option>
                                        @foreach($assetAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_purchase_return'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Biasanya mengkredit akun Persediaan Barang Dagang.</div>
                                </div>

                                {{-- Retained Earnings --}}
                                <div>
                                    <label class="form-label label-required">Akun Laba Ditahan (Retained Earnings)</label>
                                    <select name="acct_default_retained_earnings" class="tom-select">
                                        <option value="">Pilih Akun Ekuitas...</option>
                                        @foreach($equityAccounts as $coa)
                                            <option value="{{ $coa->account_id }}" {{ ($settings['acct_default_retained_earnings'] ?? '') == $coa->account_id ? 'selected' : '' }}>
                                                {{ $coa->account_number }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-hint">Tujuan pemindahan Laba/Rugi saat Tutup Buku Tahunan.</div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- FORM ACTIONS --}}
                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="material-icons text-sm mr-2">save</i> Simpan Pengaturan
                    </button>
                </div>

            </form>
        </div>

    </div>

@endsection