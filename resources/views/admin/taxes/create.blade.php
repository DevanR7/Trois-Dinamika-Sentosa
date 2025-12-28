@extends('admin.layouts.app')

@section('title', 'Tambah Pajak')

@section('content')

    <div class="max-w-2xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah Pajak</h1>
                <p class="page-subtitle">Buat aturan tarif pajak baru.</p>
            </div>
            <a href="{{ route('admin.taxes.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.taxes.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Formulir Pajak</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Nama Pajak --}}
                    <div>
                        <label class="form-label label-required">Nama Pajak</label>
                        <input type="text" name="name" 
                               class="form-input @error('name') is-invalid @enderror" 
                               placeholder="Contoh: PPN 11%, PPh 23" 
                               value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Rate --}}
                    <div>
                        <label class="form-label label-required">Persentase Tarif</label>
                        <div class="input-group">
                            <input type="number" name="rate" step="0.01" min="0" max="100"
                                   class="form-input text-right @error('rate') is-invalid @enderror" 
                                   placeholder="0" 
                                   value="{{ old('rate') }}" required>
                            <span class="input-group-text bg-slate-50 dark:bg-slate-700 font-bold">%</span>
                        </div>
                        <p class="form-hint">Gunakan titik (.) untuk desimal, contoh: 11.5</p>
                        @error('rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Status Aktif</span>
                            <p class="text-xs text-slate-500">Pajak ini dapat dipilih saat transaksi.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Pajak
                </button>
            </div>

        </form>
    </div>

@endsection