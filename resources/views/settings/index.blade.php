@extends('layouts.app')

@section('title', 'Pengaturan Perusahaan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Pengaturan Perusahaan & Akuntansi</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola profil perusahaan dan konfigurasi akun default sistem.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: INFO PERUSAHAAN (CARD) --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                    <i class="material-icons text-indigo-500">business</i>
                    <h5 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Profil Singkat</h5>
                </div>
                
                <div class="p-6">
                    {{-- Logo & Nama --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <i class="material-icons text-3xl">apartment</i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-lg leading-tight">{{ $settings['company_name'] ?? 'Nama Perusahaan' }}</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                v{{ $settings['system_version'] ?? '1.0.0' }}
                            </span>
                        </div>
                    </div>

                    {{-- List Info --}}
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="mt-0.5 text-gray-400"><i class="material-icons text-lg">person</i></div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Pemilik</p>
                                <p class="text-sm font-medium text-gray-900">{{ $settings['company_owner'] ?? '-' }}</p>
                            </div>
                        </div>

                        @if(!empty($settings['company_phone']))
                        <div class="flex gap-3">
                            <div class="mt-0.5 text-gray-400"><i class="material-icons text-lg">phone</i></div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Telepon</p>
                                <p class="text-sm font-medium text-gray-900">{{ $settings['company_phone'] }}</p>
                            </div>
                        </div>
                        @endif

                        <div class="flex gap-3">
                            <div class="mt-0.5 text-gray-400"><i class="material-icons text-lg">location_on</i></div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">Lokasi</p>
                                <p class="text-sm font-medium text-gray-900">{{ $settings['company_city_province'] ?? '-' }}</p>
                            </div>
                        </div>

                        @if(!empty($settings['company_npwp']))
                        <div class="flex gap-3">
                            <div class="mt-0.5 text-gray-400"><i class="material-icons text-lg">badge</i></div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase">NPWP</p>
                                <p class="text-sm font-medium text-gray-900 font-mono">{{ $settings['company_npwp'] }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="material-icons text-[14px]">info</i>
                        Data ini digunakan untuk kop surat.
                    </p>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: FORM PENGATURAN --}}
        <div class="lg:col-span-2">
            <form action="{{ route('settings.update') }}" method="POST" id="settings-form">
                @csrf
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    {{-- TABS HEADER --}}
                    <div class="border-b border-gray-200 bg-white px-6 pt-4 flex flex-wrap justify-between items-center gap-4">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                            <button type="button" class="tab-link active group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors text-indigo-600 border-indigo-600" data-target="#company-tab">
                                <i class="material-icons text-lg mr-2 group-[.active]:text-indigo-600 text-gray-400">domain</i>
                                Data Perusahaan
                            </button>
                            <button type="button" class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors" data-target="#system-tab">
                                <i class="material-icons text-lg mr-2 group-[.active]:text-indigo-600 text-gray-400">settings_suggest</i>
                                Sistem
                            </button>
                            <button type="button" class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors" data-target="#accounting-tab">
                                <i class="material-icons text-lg mr-2 group-[.active]:text-indigo-600 text-gray-400">account_balance_wallet</i>
                                Akun Default
                            </button>
                        </nav>

                        {{-- EDIT BUTTON --}}
                        <button type="button" id="btn-edit-settings" class="mb-2 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                            <i class="material-icons text-sm mr-1.5">edit</i> Buka Kunci Edit
                        </button>
                    </div>

                    {{-- FORM FIELDSET (DISABLED BY DEFAULT) --}}
                    <fieldset id="settings-form-fieldset" disabled class="disabled:opacity-75 transition-opacity duration-200">
                        <div class="p-6">
                            
                            {{-- TAB 1: DATA PERUSAHAAN --}}
                            <div id="company-tab" class="tab-content">
                                <h5 class="text-base font-bold text-gray-900 mb-4">Informasi Legal Perusahaan</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <div>
                                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan <span class="text-red-500">*</span></label>
                                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    </div>

                                    <div>
                                        <label for="company_owner" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik <span class="text-red-500">*</span></label>
                                        <input type="text" name="company_owner" id="company_owner" value="{{ old('company_owner', $settings['company_owner'] ?? '') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="company_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                                        <textarea name="company_address" id="company_address" rows="3" class="form-textarea w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="company_city_province" class="block text-sm font-medium text-gray-700 mb-1">Kota & Provinsi</label>
                                        <input type="text" name="company_city_province" id="company_city_province" value="{{ old('company_city_province', $settings['company_city_province'] ?? '') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                                        <input type="text" name="company_phone" id="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="company_npwp" class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                                        <input type="text" name="company_npwp" id="company_npwp" value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="00.000.000.0-000.000">
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: SISTEM --}}
                            <div id="system-tab" class="tab-content hidden">
                                <h5 class="text-base font-bold text-gray-900 mb-4">Konfigurasi Sistem</h5>
                                <div>
                                    <label for="system_version" class="block text-sm font-medium text-gray-700 mb-1">Versi Sistem</label>
                                    <input type="text" name="system_version" id="system_version" value="{{ old('system_version', $settings['system_version'] ?? '1.0.0') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <p class="mt-1 text-xs text-gray-500">Digunakan untuk pelacakan versi aplikasi internal.</p>
                                </div>
                            </div>

                            {{-- TAB 3: AKUN DEFAULT --}}
                            <div id="accounting-tab" class="tab-content hidden">
                                <h5 class="text-base font-bold text-gray-900 mb-2">Pengaturan Akun Default (COA)</h5>
                                <p class="text-sm text-gray-500 mb-6">Tentukan akun default untuk penjurnalan otomatis. Perubahan di sini akan mempengaruhi transaksi baru.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    {{-- SEKSI PENJUALAN --}}
                                    <div class="md:col-span-2 border-b border-gray-100 pb-2 mt-2">
                                        <h6 class="text-sm font-bold text-indigo-600 uppercase tracking-wide">Penjualan & Piutang</h6>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Piutang Usaha (AR)</label>
                                        <select name="acct_default_ar" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($assetAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ar'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Pendapatan Penjualan</label>
                                        <select name="acct_default_sales_revenue" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($revenueAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_revenue'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Retur Penjualan</label>
                                        <select name="acct_default_sales_return" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($expenseOrRevenueAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_return'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Deposit Klien</label>
                                        <select name="acct_default_client_deposit" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($assetOrLiabilityAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_client_deposit'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- SEKSI PEMBELIAN --}}
                                    <div class="md:col-span-2 border-b border-gray-100 pb-2 mt-4">
                                        <h6 class="text-sm font-bold text-indigo-600 uppercase tracking-wide">Pembelian & Persediaan</h6>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Hutang Dagang (AP)</label>
                                        <select name="acct_default_ap" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($liabilityAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ap'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Persediaan Barang</label>
                                        <select name="acct_default_inventory" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($assetAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun HPP (COGS)</label>
                                        <select name="acct_default_cogs" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($cogsAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_cogs'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Beban Selisih Stok</label>
                                        <select name="acct_default_inventory_adjustment" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($expenseOrRevenueAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory_adjustment'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- SEKSI EKUITAS --}}
                                    <div class="md:col-span-2 border-b border-gray-100 pb-2 mt-4">
                                        <h6 class="text-sm font-bold text-indigo-600 uppercase tracking-wide">Ekuitas</h6>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Laba Ditahan</label>
                                        <select name="acct_default_retained_earnings" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($equityAccounts as $account)
                                                <option value="{{ $account->account_id }}" @selected(($settings['acct_default_retained_earnings'] ?? '') == $account->account_id)>
                                                    {{ $account->account_number }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>

                        </div>

                        {{-- FOOTER ACTIONS --}}
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button type="button" id="btn-cancel-lock" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition">
                                <i class="material-icons text-lg mr-2">save</i> Simpan Perubahan
                            </button>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- TAB SWITCHING LOGIC (TAILWIND STYLE) ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Reset active state links
                tabLinks.forEach(l => {
                    l.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
                    l.classList.add('text-gray-500', 'border-transparent');
                    l.querySelector('i').classList.remove('text-indigo-600');
                    l.querySelector('i').classList.add('text-gray-400');
                });

                // Set active current link
                this.classList.add('active', 'text-indigo-600', 'border-indigo-600');
                this.classList.remove('text-gray-500', 'border-transparent');
                this.querySelector('i').classList.add('text-indigo-600');
                this.querySelector('i').classList.remove('text-gray-400');

                // Show target content
                const targetId = this.dataset.target;
                tabContents.forEach(content => content.classList.add('hidden'));
                document.querySelector(targetId).classList.remove('hidden');
            });
        });

        // --- LOCK/UNLOCK FORM LOGIC ---
        const fieldset = document.getElementById('settings-form-fieldset');
        const editButton = document.getElementById('btn-edit-settings');
        const cancelButton = document.getElementById('btn-cancel-lock');

        if (fieldset && editButton && cancelButton) {
            editButton.addEventListener('click', function() {
                fieldset.disabled = false; 
                fieldset.classList.remove('opacity-75');
                this.classList.add('hidden');
            });

            cancelButton.addEventListener('click', function() {
                fieldset.disabled = true;
                fieldset.classList.add('opacity-75');
                editButton.classList.remove('hidden');
                
                // Optional: Reset form to initial state (Reload page easier)
                location.reload();
            });
        }

        // --- NOTIFIKASI ---
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        @endif
    });
</script>
@endpush