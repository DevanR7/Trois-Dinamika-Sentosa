@extends('admin.layouts.app')

@section('title', 'Tambah Satuan')

@section('content')

    <div class="max-w-xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah Satuan</h1>
                <p class="page-subtitle">Buat satuan baru untuk produk.</p>
            </div>
            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.units.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Formulir Satuan</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Nama Satuan --}}
                    <div>
                        <label class="form-label label-required">Nama Satuan</label>
                        <input type="text" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               placeholder="Contoh: Pcs, Kg, Box, Lusin" 
                               value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Satuan
                </button>
            </div>

        </form>
    </div>

@endsection