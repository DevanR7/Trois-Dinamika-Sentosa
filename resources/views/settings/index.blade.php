@extends('layouts.app')

@section('content')
<div class="container py-4">
    
    {{-- JUDUL HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Pengaturan Perusahaan & Akuntansi</h2>
    </div>

    <div class="row">
        
        {{-- KOLOM KIRI (INFORMASI PERUSAHAAN - READ ONLY / CARD) --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 2rem;">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>Profil Singkat
                    </h5>
                </div>
                <div class="card-body">
                    
                    {{-- Logo Perusahaan (Bulat) --}}
                    <div class="d-flex align-items-center justify-content-start mb-4">
                        <div class="d-flex align-items-center justify-content-center 
                                    bg-light text-muted rounded-circle border me-3" 
                             style="width: 80px; height: 80px; font-size: 1.8rem;">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">{{ $settings['company_name'] ?? 'Nama Perusahaan' }}</h4>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border-0">
                                <i class="bi bi-tag-fill me-1"></i>
                                Versi {{ $settings['system_version'] ?? '1.0.0' }}
                            </span>
                        </div>
                    </div>

                    {{-- Informasi Detail --}}
                    <div class="company-info">
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle me-3" style="width: 36px; height: 36px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Pemilik</small>
                                    <span class="fw-semibold">{{ $settings['company_owner'] ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($settings['company_phone']))
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle me-3" style="width: 36px; height: 36px;">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Telepon</small>
                                    <span class="fw-semibold">{{ $settings['company_phone'] }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle me-3" style="width: 36px; height: 36px;">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Lokasi</small>
                                    <span class="fw-semibold">{{ $settings['company_city_province'] ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        @if(!empty($settings['company_npwp']))
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle me-3" style="width: 36px; height: 36px;">
                                    <i class="bi bi-file-text-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">NPWP</small>
                                    <span class="fw-semibold">{{ $settings['company_npwp'] }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 pt-0">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Data ini digunakan untuk kop surat dan laporan.
                    </small>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (FORM PENGATURAN) --}}
        <div class="col-lg-8">
            
            <form action="{{ route('settings.update') }}" method="POST" id="settings-form">
                @csrf
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-tab-pane" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-building me-2"></i>Data Perusahaan
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-tab-pane" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-gear-fill me-2"></i>Sistem
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="accounting-tab" data-bs-toggle="tab" data-bs-target="#accounting-tab-pane" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-journal-bookmark-fill me-2"></i>Akun Default
                                </button>
                            </li>
                        </ul>
                        
                        {{-- TOMBOL BUKA KUNCI --}}
                        <button type="button" class="btn btn-outline-dark btn-sm mb-2" id="btn-edit-settings">
                            <i class="bi bi-pencil-fill me-1"></i> Buka Kunci Edit
                        </button>
                    </div>
                    
                    {{-- FIELDSET UNTUK LOCK FORM --}}
                    <fieldset id="settings-form-fieldset" disabled>
                        <div class="card-body p-4">
                            <div class="tab-content" id="settingsTabsContent">
                                
                                {{-- TAB 1: DATA PERUSAHAAN --}}
                                <div class="tab-pane fade show active" id="company-tab-pane" role="tabpanel" tabindex="0">
                                    <h5 class="fw-semibold mb-4">Informasi Legal Perusahaan</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="company_name" class="form-label fw-semibold">Nama Perusahaan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required>
                                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="company_owner" class="form-label fw-semibold">Nama Pemilik <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('company_owner') is-invalid @enderror" id="company_owner" name="company_owner" value="{{ old('company_owner', $settings['company_owner'] ?? '') }}" required>
                                            @error('company_owner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="company_address" class="form-label fw-semibold">Alamat Lengkap</label>
                                            <textarea class="form-control @error('company_address') is-invalid @enderror" id="company_address" name="company_address" rows="3">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                            @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="company_city_province" class="form-label fw-semibold">Kota & Provinsi</label>
                                            <input type="text" class="form-control @error('company_city_province') is-invalid @enderror" id="company_city_province" name="company_city_province" value="{{ old('company_city_province', $settings['company_city_province'] ?? '') }}">
                                            @error('company_city_province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="company_phone" class="form-label fw-semibold">No. Telepon</label>
                                            <input type="text" class="form-control @error('company_phone') is-invalid @enderror" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}">
                                            @error('company_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="company_npwp" class="form-label fw-semibold">NPWP</label>
                                            <input type="text" class="form-control @error('company_npwp') is-invalid @enderror" id="company_npwp" name="company_npwp" value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" placeholder="12.345.678.9-012.345">
                                            @error('company_npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- TAB 2: PENGATURAN SISTEM --}}
                                <div class="tab-pane fade" id="system-tab-pane" role="tabpanel" tabindex="0">
                                    <h5 class="fw-semibold mb-4">Konfigurasi Sistem</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="system_version" class="form-label fw-semibold">Versi Sistem</label>
                                            <input type="text" class="form-control @error('system_version') is-invalid @enderror" id="system_version" name="system_version" value="{{ old('system_version', $settings['system_version'] ?? '1.0.0') }}">
                                            @error('system_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            <div class="form-text">Digunakan untuk pelacakan versi aplikasi internal.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- TAB 3: PENGATURAN AKUNTANSI --}}
                                <div class="tab-pane fade" id="accounting-tab-pane" role="tabpanel" tabindex="0">
                                    <h5 class="fw-semibold mb-4">Pengaturan Akun Default (COA)</h5>
                                    <p class="text-muted small mb-4">Tentukan akun default untuk penjurnalan otomatis. Perubahan di sini akan mempengaruhi transaksi baru.</p>
                                    
                                    <div class="row g-3">
                                        {{-- PENJUALAN --}}
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-0 mt-2">Penjualan & Piutang</h6>
                                        
                                        <div class="col-md-6">
                                            <label for="acct_default_ar" class="form-label">Akun Piutang Usaha (AR)</label>
                                            <select class="form-select" id="acct_default_ar" name="acct_default_ar">
                                                <option value="">-- Pilih Akun Aset --</option>
                                                @foreach ($assetAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ar'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_sales_revenue" class="form-label">Akun Pendapatan Penjualan</label>
                                            <select class="form-select" id="acct_default_sales_revenue" name="acct_default_sales_revenue">
                                                <option value="">-- Pilih Akun Pendapatan --</option>
                                                @foreach ($revenueAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_revenue'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_sales_return" class="form-label">Akun Retur Penjualan</label>
                                            <select class="form-select" id="acct_default_sales_return" name="acct_default_sales_return">
                                                <option value="">-- Pilih Akun --</option>
                                                @foreach ($expenseOrRevenueAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_sales_return'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_client_deposit" class="form-label">Akun Deposit Klien (Liability)</label>
                                            <select class="form-select" id="acct_default_client_deposit" name="acct_default_client_deposit">
                                                <option value="">-- Pilih Akun Kewajiban --</option>
                                                @foreach ($assetOrLiabilityAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_client_deposit'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_gateway" class="form-label">Akun Penampung Gateway (Midtrans)</label>
                                            <select class="form-select" id="acct_default_gateway" name="acct_default_gateway">
                                                <option value="">-- Pilih Akun Aset --</option>
                                                @foreach ($assetAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_gateway'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- PEMBELIAN --}}
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-0 mt-4">Pembelian & Persediaan</h6>
                                        
                                        <div class="col-md-6">
                                            <label for="acct_default_ap" class="form-label">Akun Hutang Dagang (AP)</label>
                                            <select class="form-select" id="acct_default_ap" name="acct_default_ap">
                                                <option value="">-- Pilih Akun Kewajiban --</option>
                                                @foreach ($liabilityAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_ap'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_inventory" class="form-label">Akun Persediaan Barang</label>
                                            <select class="form-select" id="acct_default_inventory" name="acct_default_inventory">
                                                <option value="">-- Pilih Akun Aset --</option>
                                                @foreach ($assetAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_cogs" class="form-label">Akun HPP (COGS)</label>
                                            <select class="form-select" id="acct_default_cogs" name="acct_default_cogs">
                                                <option value="">-- Pilih Akun HPP --</option>
                                                @foreach ($cogsAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_cogs'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_purchase_return" class="form-label">Akun Retur Pembelian</label>
                                            <select class="form-select" id="acct_default_purchase_return" name="acct_default_purchase_return">
                                                <option value="">-- Pilih Akun Aset --</option>
                                                @foreach ($assetAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_purchase_return'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_supplier_deposit" class="form-label">Akun Deposit Supplier</label>
                                            <select class="form-select" id="acct_default_supplier_deposit" name="acct_default_supplier_deposit">
                                                <option value="">-- Pilih Akun Aset --</option>
                                                @foreach ($assetOrLiabilityAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_supplier_deposit'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="acct_default_inventory_adjustment" class="form-label">Akun Beban Selisih Stok</label>
                                            <select class="form-select" id="acct_default_inventory_adjustment" name="acct_default_inventory_adjustment">
                                                <option value="">-- Pilih Akun Beban --</option>
                                                @foreach ($expenseOrRevenueAccounts as $account)
                                                    <option value="{{ $account->account_id }}" @selected(($settings['acct_default_inventory_adjustment'] ?? '') == $account->account_id)>
                                                        {{ $account->account_number }} - {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- EKUITAS --}}
                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-0 mt-4">Ekuitas</h6>
                                        <div class="col-md-6">
                                            <label for="acct_default_retained_earnings" class="form-label">Akun Laba Ditahan</label>
                                            <select class="form-select" id="acct_default_retained_earnings" name="acct_default_retained_earnings">
                                                <option value="">-- Pilih Akun Ekuitas --</option>
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
                        
                        {{-- TOMBOL SIMPAN --}}
                        <div class="card-footer bg-white border-top text-end">
                            <button type="reset" class="btn btn-light border me-2" id="btn-cancel-lock">Batal</button>
                            <button type="submit" class="btn btn-primary fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .nav-tabs .nav-link { font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1rem; }
    .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 2px solid #0d6efd; background: transparent; }
    .nav-tabs .nav-link:hover { border-bottom: 2px solid #dee2e6; }
    .sticky-top { z-index: 1; }
    .rounded-circle { border-radius: 50% !important; }
    .company-info .info-item { padding: 8px 0; border-bottom: 1px solid #f8f9fa; }
    .company-info .info-item:last-child { border-bottom: none; }
    #btn-edit-settings { transition: opacity 0.3s ease; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // SweetAlert Notifikasi
        @if(session('success'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000, timerProgressBar: true });
        @endif

        // Logika Lock/Unlock Form
        const fieldset = document.getElementById('settings-form-fieldset');
        const editButton = document.getElementById('btn-edit-settings');
        const cancelButton = document.getElementById('btn-cancel-lock');

        if (fieldset && editButton && cancelButton) {
            editButton.addEventListener('click', function() {
                fieldset.disabled = false; 
                editButton.style.display = 'none'; 
            });

            cancelButton.addEventListener('click', function() {
                fieldset.disabled = true; 
                editButton.style.display = 'inline-block'; 
            });
        }
    });
</script>
@endpush    