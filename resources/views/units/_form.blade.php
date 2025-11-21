@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="name" class="form-label fw-bold small text-muted">NAMA SATUAN <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $unit->name ?? '') }}" required placeholder="Contoh: Pcs, Box, Kg">
    <div class="form-text">Gunakan nama satuan yang singkat dan jelas (misal: Kg, Ltr, Pcs).</div>
</div>