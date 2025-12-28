@extends('admin.layouts.app')

@section('title', 'Edit Satuan')

@section('content')

    <div class="max-w-xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Satuan: {{ $unit->name }}</h1>
                <p class="page-subtitle">Perbarui nama satuan.</p>
            </div>
            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.units.update', $unit->unit_id) }}" method="POST">
            @csrf
            @method('PUT')

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
                               value="{{ old('name', $unit->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- Submit & Delete Actions --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">delete</i> Hapus
                </button>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        <form id="deleteForm" action="{{ route('admin.units.destroy', $unit->unit_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Satuan?',
            text: "Data ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush