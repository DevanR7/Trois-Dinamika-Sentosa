@extends('admin.layouts.app')

@section('title', 'Edit Satuan')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Satuan</h1>
            <p class="page-subtitle">Perbarui informasi satuan: {{ $unit->name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                Kembali
            </a>
        </div>
    </div>

    <div class="max-w-xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.units.update', $unit->unit_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Nama Satuan --}}
                    <div class="form-group mb-6">
                        <label for="name" class="form-label label-required">Nama Satuan</label>
                        <input type="text" id="name" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               value="{{ old('name', $unit->name) }}" 
                               placeholder="Contoh: Pcs, Box, Kg">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="form-group mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                {{ old('is_active', $unit->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">Aktifkan Satuan Ini</span>
                        </label>
                        <p class="text-xs text-slate-400 mt-1 ml-6">Non-aktifkan jika satuan ini tidak lagi digunakan untuk produk baru.</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons text-[18px]">save</i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection