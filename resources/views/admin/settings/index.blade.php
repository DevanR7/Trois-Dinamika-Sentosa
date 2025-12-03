@extends('admin.layouts.app')

@section('title', 'Pengaturan Perusahaan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan Sistem</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola profil perusahaan dan konfigurasi akuntansi.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: INFO PERUSAHAAN (STICKY CARD) --}}
        <div class="lg:col-span-1">
            <div class="dashboard-card p-0 overflow-hidden sticky top-6 shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                        <i class="material-icons text-[20px]">business</i>
                    </div>
                    <h5 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Profil Singkat</h5>
                </div>
                
                <div class="p-6">
                    {{-- Logo & Nama --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <i class="material-icons text-3xl">apartment</i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-800 text-base leading-tight truncate" title="{{ $settings['company_name'] ?? 'Nama Perusahaan' }}">
                                {{ $settings['company_name'] ?? 'Nama Perusahaan' }}
                            </h4>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 mt-1">
                                v{{ $settings['system_version'] ?? '1.0.0' }} <i class="material-icons text-[12px]">verified</i>
                            </span>
                        </div>
                    </div>

                    {{-- List Info --}}
                    <div class="space-y-4 pt-4 border-t border-slate-100 text-sm">
                        <div class="flex gap-3">
                            <i class="material-icons text-slate-400 text-[18px] mt-0.5">person</i>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Pemilik</p>
                                <p class="font-medium text-slate-700">{{ $settings['company_owner'] ?? '-' }}</p>
                            </div>
                        </div>

                        @if(!empty($settings['company_phone']))
                        <div class="flex gap-3">
                            <i class="material-icons text-slate-400 text-[18px] mt-0.5">phone</i>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Telepon</p>
                                <p class="font-medium text-slate-700">{{ $settings['company_phone'] }}</p>
                            </div>
                        </div>
                        @endif

                        <div class="flex gap-3">
                            <i class="material-icons text-slate-400 text-[18px] mt-0.5">location_on</i>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Lokasi</p>
                                <p class="font-medium text-slate-700 leading-relaxed">{{ $settings['company_city_province'] ?? '-' }}</p>
                            </div>
                        </div>

                        @if(!empty($settings['company_npwp']))
                        <div class="flex gap-3">
                            <i class="material-icons text-slate-400 text-[18px] mt-0.5">badge</i>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">NPWP</p>
                                <p class="font-medium text-slate-700 font-mono">{{ $settings['company_npwp'] }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="bg-slate-50 px-6 py-3 border-t border-slate-100">
                    <p class="text-[11px] text-slate-500 flex items-center gap-1">
                        <i class="material-icons text-[14px]">info</i>
                        Data ini digunakan untuk kop surat & invoice.
                    </p>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: FORM PENGATURAN --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.settings.update') }}" method="POST" id="settings-form">
                @csrf
                <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                    
                    {{-- TABS HEADER --}}
                    <div class="border-b border-slate-200 bg-white px-6 pt-2 flex flex-wrap justify-between items-center gap-4 sticky top-0 z-10">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto no-scrollbar" aria-label="Tabs">
                            <button type="button" class="tab-link active group inline-flex items-center py-4 px-1 border-b-2 border-indigo-600 font-bold text-sm text-indigo-600 transition-colors whitespace-nowrap" data-target="#company-tab">
                                <i class="material-icons text-[18px] mr-2">domain</i> Data Perusahaan
                            </button>
                            <button type="button" class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors whitespace-nowrap" data-target="#system-tab">
                                <i class="material-icons text-[18px] mr-2 text-slate-400 group-hover:text-slate-500">settings_suggest</i> Sistem
                            </button>
                            <button type="button" class="tab-link group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors whitespace-nowrap" data-target="#accounting-tab">
                                <i class="material-icons text-[18px] mr-2 text-slate-400 group-hover:text-slate-500">account_balance_wallet</i> Akun Default
                            </button>
                        </nav>

                        {{-- EDIT BUTTON --}}
                        <button type="button" id="btn-edit-settings" class="my-2 inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs font-bold rounded-lg transition-all border border-indigo-100">
                            <i class="material-icons text-[16px] mr-1.5">edit</i> Buka Kunci
                        </button>
                    </div>

                    {{-- FORM FIELDSET (DISABLED BY DEFAULT) --}}
                    <fieldset id="settings-form-fieldset" disabled class="disabled:opacity-60 transition-opacity duration-200 bg-white">
                        <div class="p-6 md:p-8 min-h-[400px]">
                            
                            {{-- TAB 1: DATA PERUSAHAAN --}}
                            <div id="company-tab" class="tab-content space-y-6 animate-enter">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <div>
                                        <label for="company_name">Nama Perusahaan <span class="text-red-500">*</span></label>
                                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-input" required>
                                    </div>

                                    <div>
                                        <label for="company_owner">Nama Pemilik <span class="text-red-500">*</span></label>
                                        <input type="text" name="company_owner" id="company_owner" value="{{ old('company_owner', $settings['company_owner'] ?? '') }}" class="form-input" required>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="company_address">Alamat Lengkap</label>
                                        <textarea name="company_address" id="company_address" rows="3" class="form-textarea">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="company_city_province">Kota & Provinsi</label>
                                        <input type="text" name="company_city_province" id="company_city_province" value="{{ old('company_city_province', $settings['company_city_province'] ?? '') }}" class="form-input">
                                    </div>

                                    <div>
                                        <label for="company_phone">No. Telepon</label>
                                        <input type="text" name="company_phone" id="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" class="form-input">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="company_npwp">NPWP</label>
                                        <input type="text" name="company_npwp" id="company_npwp" value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" class="form-input font-mono" placeholder="00.000.000.0-000.000">
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: SISTEM --}}
                            <div id="system-tab" class="tab-content hidden animate-enter">
                                <div class="max-w-md">
                                    <label for="system_version">Versi Sistem</label>
                                    <input type="text" name="system_version" id="system_version" value="{{ old('system_version', $settings['system_version'] ?? '1.0.0') }}" class="form-input font-mono">
                                    <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                                        <i class="material-icons text-[14px]">info</i> Digunakan untuk pelacakan versi aplikasi internal.
                                    </p>
                                </div>
                            </div>

                            {{-- TAB 3: AKUN DEFAULT --}}
                            <div id="accounting-tab" class="tab-content hidden animate-enter">
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6 flex gap-3 text-blue-800">
                                    <i class="material-icons text-xl">info</i>
                                    <div class="text-sm">
                                        <p class="font-bold mb-1">Penting!</p>
                                        <p>Tentukan akun default untuk penjurnalan otomatis. Perubahan di sini akan mempengaruhi transaksi baru ke depannya.</p>
                                    </div>
                                </div>

                                <div class="space-y-8">
                                    
                                    {{-- SEKSI PENJUALAN --}}
                                    <div>
                                        <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wide border-b border-indigo-100 pb-2 mb-4">Penjualan & Piutang</h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label>Akun Piutang Usaha (AR)</label>
                                                <select name="acct_default_ar" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($assetAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ar'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun Pendapatan Penjualan</label>
                                                <select name="acct_default_sales_revenue" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($revenueAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_revenue'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun Retur Penjualan</label>
                                                <select name="acct_default_sales_return" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($expenseOrRevenueAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_return'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun Deposit Klien</label>
                                                <select name="acct_default_client_deposit" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($assetOrLiabilityAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_client_deposit'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SEKSI PEMBELIAN --}}
                                    <div>
                                        <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wide border-b border-indigo-100 pb-2 mb-4">Pembelian & Persediaan</h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label>Akun Hutang Dagang (AP)</label>
                                                <select name="acct_default_ap" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($liabilityAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ap'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun Persediaan Barang</label>
                                                <select name="acct_default_inventory" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($assetAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun HPP (COGS)</label>
                                                <select name="acct_default_cogs" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($cogsAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_cogs'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label>Akun Beban Selisih Stok</label>
                                                <select name="acct_default_inventory_adjustment" class="form-input select2-coa">
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($expenseOrRevenueAccounts as $account)
                                                        <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory_adjustment'] ?? '') == $account->account_id)>
                                                            {{ $account->account_number }} - {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SEKSI EKUITAS --}}
                                    <div>
                                        <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wide border-b border-indigo-100 pb-2 mb-4">Ekuitas</h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label>Akun Laba Ditahan</label>
                                                <select name="acct_default_retained_earnings" class="form-input select2-coa">
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
                            </div>

                        </div>

                        {{-- FOOTER ACTIONS --}}
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button type="button" id="btn-cancel-lock" class="hidden h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                                <i class="material-icons text-[18px] mr-2">close</i> Batal
                            </button>
                            <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="material-icons text-[18px]">save</i> Simpan Perubahan
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Init Select2 ---
        $('.select2-coa').select2({ placeholder: '-- Pilih Akun --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // --- 2. Tab Logic ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        const activateTab = (targetId, clickedLink) => {
            // Reset Style Link
            tabLinks.forEach(l => {
                l.classList.remove('active', 'text-indigo-600', 'border-indigo-600');
                l.classList.add('text-slate-500', 'border-transparent');
                l.querySelector('i').classList.remove('text-indigo-600');
                l.querySelector('i').classList.add('text-slate-400');
            });

            // Active Style
            clickedLink.classList.add('active', 'text-indigo-600', 'border-indigo-600');
            clickedLink.classList.remove('text-slate-500', 'border-transparent');
            clickedLink.querySelector('i').classList.add('text-indigo-600');
            clickedLink.querySelector('i').classList.remove('text-slate-400');

            // Content Visibility
            tabContents.forEach(content => content.classList.add('hidden'));
            document.querySelector(targetId).classList.remove('hidden');
        };

        tabLinks.forEach(link => {
            link.addEventListener('click', function() { activateTab(this.dataset.target, this); });
        });

        // Default Tab
        if(tabLinks.length > 0) activateTab(tabLinks[0].dataset.target, tabLinks[0]);

        // --- 3. Lock/Unlock Form ---
        const fieldset = document.getElementById('settings-form-fieldset');
        const editButton = document.getElementById('btn-edit-settings');
        const cancelButton = document.getElementById('btn-cancel-lock');

        if (fieldset && editButton && cancelButton) {
            editButton.addEventListener('click', function() {
                fieldset.disabled = false; 
                fieldset.classList.remove('disabled:opacity-60');
                this.classList.add('hidden');
                cancelButton.classList.remove('hidden'); 
            });

            cancelButton.addEventListener('click', function() {
                Swal.fire({
                    title: 'Batalkan Perubahan?',
                    text: "Perubahan yang belum disimpan akan hilang.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Lanjut Edit',
                    reverseButtons: true, // Opsional: agar tombol Batal di kiri
                    customClass: {
                        // PERBAIKAN: Hapus 'colored-toast', ganti dengan style modal standar
                        popup: 'bg-white rounded-xl border border-slate-100 shadow-2xl p-6',
                        title: 'text-xl font-bold text-slate-800',
                        htmlContainer: 'text-sm text-slate-600 mt-2',
                        confirmButton: 'px-5 py-2.5 rounded-lg font-bold shadow-md',
                        cancelButton: 'px-5 py-2.5 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                    }
                }).then((result) => {
                    if (result.isConfirmed) location.reload(); 
                });
            });
        }

        // --- 4. Notifikasi ---
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush