@extends('admin.layouts.app')

@section('title', 'Edit Pajak')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Pajak</h1>
            <p class="page-subtitle">Perbarui informasi tarif pajak: {{ $tax->name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.taxes.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                Kembali
            </a>
        </div>
    </div>

    <div class="max-w-xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.taxes.update', $tax->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Nama Pajak --}}
                    <div class="form-group mb-4">
                        <label for="name" class="form-label label-required">Nama Pajak</label>
                        <input type="text" id="name" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               value="{{ old('name', $tax->name) }}" 
                               placeholder="Contoh: PPN 11%">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Persentase Tarif --}}
                    <div class="form-group mb-6">
                        <label for="rate" class="form-label label-required">Tarif (%)</label>
                        <div class="relative">
                            <input type="number" id="rate" name="rate" step="0.01" min="0" max="100"
                                   class="form-input pr-8 @error('rate') is-invalid @enderror" 
                                   value="{{ old('rate', $tax->rate) }}" 
                                   placeholder="Contoh: 11">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 font-bold">%</span>
                            </div>
                        </div>
                        @error('rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="form-group mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                {{ old('is_active', $tax->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">Aktifkan Pajak Ini</span>
                        </label>
                        <p class="text-xs text-slate-400 mt-1 ml-6">Non-aktifkan jika tarif ini sudah tidak berlaku, namun jangan dihapus jika sudah digunakan dalam transaksi.</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a href="{{ route('admin.taxes.index') }}" class="btn btn-secondary">Batal</a>
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