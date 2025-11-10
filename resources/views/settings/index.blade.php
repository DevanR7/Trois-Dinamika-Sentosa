@extends('layouts.app')

@section('content')
<div class="container py-4">
    
    {{-- JUDUL HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Pengaturan Perusahaan</h2>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        
        {{-- KOLOM KIRI (INFORMASI PERUSAHAAN - READ ONLY) --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 2rem;">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>Informasi Perusahaan
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
                                <div class="d-flex align-items-center justify-content-center 
                                            bg-light text-muted rounded-circle me-3" 
                                     style="width: 36px; height: 36px; font-size: 0.9rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Nama Pemilik</small>
                                    <span class="fw-semibold">{{ $settings['company_owner'] ?? 'Pemilik Belum Diatur' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($settings['company_phone']))
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center 
                                            bg-light text-muted rounded-circle me-3" 
                                     style="width: 36px; height: 36px; font-size: 0.9rem;">
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
                                <div class="d-flex align-items-center justify-content-center 
                                            bg-light text-muted rounded-circle me-3" 
                                     style="width: 36px; height: 36px; font-size: 0.9rem;">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Lokasi</small>
                                    <span class="fw-semibold">{{ $settings['company_city_province'] ?? 'Kota/Provinsi Belum Diatur' }}</span>
                                </div>
                            </div>
                        </div>

                        @if(!empty($settings['company_npwp']))
                        <div class="info-item mb-3">
                            <div class="d-flex align-items-start">
                                <div class="d-flex align-items-center justify-content-center 
                                            bg-light text-muted rounded-circle me-3" 
                                     style="width: 36px; height: 36px; font-size: 0.9rem;">
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
                        Informasi ini ditampilkan pada dokumen dan laporan sistem
                    </small>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (FORM PENGATURAN) --}}
        <div class="col-lg-8">
            
            {{-- BLOK FORM DENGAN TABS --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-tab-pane" type="button" role="tab" aria-controls="company-tab-pane" aria-selected="true">
                                <i class="bi bi-building me-2"></i>Data Perusahaan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-tab-pane" type="button" role="tab" aria-controls="system-tab-pane" aria-selected="false">
                                <i class="bi bi-gear-fill me-2"></i>Pengaturan Sistem
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="tab-content" id="settingsTabsContent">
                            
                            {{-- TAB 1: DATA PERUSAHAAN --}}
                            <div class="tab-pane fade show active" id="company-tab-pane" role="tabpanel" aria-labelledby="company-tab" tabindex="0">
                                <h5 class="fw-semibold mb-4">Informasi Legal Perusahaan</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label fw-semibold">Nama Perusahaan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                               id="company_name" name="company_name" 
                                               value="{{ old('company_name', $settings['company_name'] ?? '') }}" 
                                               placeholder="Masukkan nama perusahaan" required>
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_owner" class="form-label fw-semibold">Nama Pemilik <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('company_owner') is-invalid @enderror" 
                                               id="company_owner" name="company_owner" 
                                               value="{{ old('company_owner', $settings['company_owner'] ?? '') }}" 
                                               placeholder="Masukkan nama pemilik" required>
                                        @error('company_owner')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="company_address" class="form-label fw-semibold">Alamat Lengkap</label>
                                        <textarea class="form-control @error('company_address') is-invalid @enderror" 
                                                  id="company_address" name="company_address" 
                                                  rows="3" placeholder="Masukkan alamat lengkap perusahaan">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                        @error('company_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_city_province" class="form-label fw-semibold">Kota & Provinsi</label>
                                        <input type="text" class="form-control @error('company_city_province') is-invalid @enderror" 
                                               id="company_city_province" name="company_city_province" 
                                               value="{{ old('company_city_province', $settings['company_city_province'] ?? '') }}" 
                                               placeholder="Contoh: Jakarta Barat, DKI Jakarta">
                                        @error('company_city_province')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_phone" class="form-label fw-semibold">No. Telepon</label>
                                        <input type="text" class="form-control @error('company_phone') is-invalid @enderror" 
                                               id="company_phone" name="company_phone" 
                                               value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" 
                                               placeholder="Contoh: (021) 1234567">
                                        @error('company_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="company_npwp" class="form-label fw-semibold">NPWP</label>
                                        <input type="text" class="form-control @error('company_npwp') is-invalid @enderror" 
                                               id="company_npwp" name="company_npwp" 
                                               value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" 
                                               placeholder="Format: 12.345.678.9-012.345">
                                        @error('company_npwp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            {{-- TAB 2: PENGATURAN SISTEM --}}
                            <div class="tab-pane fade" id="system-tab-pane" role="tabpanel" aria-labelledby="system-tab" tabindex="0">
                                <h5 class="fw-semibold mb-4">Konfigurasi Sistem</h5>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="system_version" class="form-label fw-semibold">Versi Sistem</label>
                                        <input type="text" class="form-control @error('system_version') is-invalid @enderror" 
                                               id="system_version" name="system_version" 
                                               value="{{ old('system_version', $settings['system_version'] ?? '1.0.0') }}" 
                                               placeholder="Contoh: 1.0.1">
                                        @error('system_version')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Versi saat ini: <strong>{{ $settings['system_version'] ?? '1.0.0' }}</strong>
                                        </div>
                                    </div>
                                    
                                    {{-- Anda bisa menambahkan lebih banyak pengaturan sistem di sini nanti --}}
                                    <div class="col-12">
                                        <div class="alert alert-info border-0">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            Pengaturan sistem lainnya akan ditambahkan sesuai kebutuhan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- TOMBOL SIMPAN --}}
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="reset" class="btn btn-outline-secondary me-2">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .nav-tabs .nav-link {
        font-weight: 500;
        border: none;
        padding: 0.75rem 1rem;
        color: #6c757d;
    }
    
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
    
    .nav-tabs .nav-link:hover {
        border: none;
        border-bottom: 2px solid #dee2e6;
    }
    
    .sticky-top {
        z-index: 1;
    }
    
    /* Pastikan semua icon container berbentuk bulat sempurna */
    .rounded-circle {
        border-radius: 50% !important;
    }
    
    .company-info .info-item {
        padding: 8px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .company-info .info-item:last-child {
        border-bottom: none;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format NPWP input
        const npwpInput = document.getElementById('company_npwp');
        if (npwpInput) {
            npwpInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.match(/.{1,15}/g)[0];
                    let formattedValue = '';
                    
                    if (value.length > 2) formattedValue += value.substr(0, 2) + '.';
                    if (value.length > 5) formattedValue += value.substr(2, 3) + '.';
                    if (value.length > 8) formattedValue += value.substr(5, 3) + '.';
                    if (value.length > 9) formattedValue += value.substr(8, 1) + '-';
                    if (value.length > 12) formattedValue += value.substr(9, 3) + '.';
                    if (value.length > 15) formattedValue += value.substr(12, 3);
                    
                    e.target.value = formattedValue;
                }
            });
        }
        
        // Format phone number
        const phoneInput = document.getElementById('company_phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.startsWith('0')) {
                        value = '(' + value.substr(0, 3) + ') ' + value.substr(3);
                    }
                    e.target.value = value;
                }
            });
        }
        
        // Notifikasi sukses dengan SweetAlert
        const sessionSuccess = @json(session('success'));
        if (sessionSuccess) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: sessionSuccess,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    });
</script>
@endpush