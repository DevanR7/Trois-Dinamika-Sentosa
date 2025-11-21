@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Satuan</h3>
            <p class="text-muted mb-0 small">Perbarui data satuan: <span class="text-primary fw-bold">{{ $unit->name }}</span></p>
        </div>
        <div>
            <a href="{{ route('units.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form action="{{ route('units.update', $unit->unit_id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-pencil-square"></i> Edit Satuan</div>
                    </div>
                    
                    <div class="card-body p-4">
                        @include('units._form')

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('units.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Update Satuan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection