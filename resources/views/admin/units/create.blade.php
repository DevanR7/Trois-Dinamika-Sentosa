@extends('admin.layouts.app')

@section('title', 'Tambah Satuan')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Satuan Baru</h1>
            <p class="page-subtitle">Buat satuan baru untuk produk (Misal: Pcs, Box, Kg)</p>
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
                <form action="{{ route('admin.units.store') }}" method="POST">
                    @csrf

                    {{-- Nama Satuan --}}
                    <div class="form-group mb-6">
                        <label for="name" class="form-label label-required">Nama Satuan</label>
                        <input type="text" id="name" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" 
                               placeholder="Contoh: Pcs, Box, Kg, Liter">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="form-group mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Aktifkan Satuan Ini</span>
                        </label>
                        <p class="text-xs text-slate-400 mt-1 ml-6">Jika tidak dicentang, satuan tidak akan muncul saat menambah/edit produk.</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons text-[18px]">save</i>
                            Simpan Satuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection