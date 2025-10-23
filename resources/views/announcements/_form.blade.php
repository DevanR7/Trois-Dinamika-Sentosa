{{-- resources/views/announcements/_form.blade.php --}}

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-12">
        <label for="title" class="form-label fw-semibold">Judul (Opsional)</label>
        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $announcement->title ?? '') }}">
    </div>

    <div class="col-12">
        <label for="content" class="form-label fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
        <textarea class="form-control" id="content" name="content" rows="5" required>{{ old('content', $announcement->content ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label for="type" class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
        <select class="form-select" id="type" name="type" required>
            <option value="broadcast" @selected(old('type', $announcement->type ?? 'broadcast') == 'broadcast')>Broadcast (Semua Klien)</option>
            <option value="targeted" @selected(old('type', $announcement->type ?? '') == 'targeted')>Targeted (Klien Tertentu)</option>
        </select>
    </div>

    {{-- Container untuk pilihan klien, awalnya tersembunyi --}}
    <div class="col-12" id="client-selection-container" style="display: {{ old('type', $announcement->type ?? 'broadcast') == 'targeted' ? 'block' : 'none' }};">
        <label for="client_ids" class="form-label fw-semibold">Pilih Klien Target <span id="client-required-indicator" class="text-danger" style="display: none;">*</span></label>
        {{-- Gunakan select2 untuk pilihan multiple --}}
        <select class="form-select select2" id="client_ids" name="client_ids[]" multiple="multiple" style="width: 100%;">
            @foreach ($clients as $client)
                <option value="{{ $client->client_id }}" 
                    {{-- Cek old input atau data dari edit --}}
                    @selected(in_array($client->client_id, old('client_ids', $selectedClientIds ?? [])))> 
                    {{ $client->client_name }} ({{ $client->email ?? 'Email Kosong' }})
                </option>
            @endforeach
        </select>
        @error('client_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('client_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" 
                   @checked(old('is_active', $announcement->is_active ?? false))>
            <label class="form-check-label" for="is_active">Aktifkan Pengumuman ini?</label>
        </div>
        <small class="text-muted">Jika dicentang, pengumuman akan langsung tampil di portal klien.</small>
    </div>
</div>

@push('styles')
    {{-- CSS untuk Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@push('scripts')
    {{-- JS untuk Select2 & logic form --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> {{-- Select2 butuh jQuery --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih satu atau lebih klien...',
                allowClear: true
            });

            // Tampilkan/sembunyikan pilihan klien berdasarkan tipe
            const typeSelect = $('#type');
            const clientContainer = $('#client-selection-container');
            const clientRequiredIndicator = $('#client-required-indicator');
            const clientSelect = $('#client_ids');

            function toggleClientSelection() {
                if (typeSelect.val() === 'targeted') {
                    clientContainer.slideDown();
                    clientRequiredIndicator.show();
                    // Optional: tambahkan atribut required ke select2
                    // clientSelect.prop('required', true); 
                } else {
                    clientContainer.slideUp();
                    clientRequiredIndicator.hide();
                    // Hapus pilihan & required jika broadcast
                    clientSelect.val(null).trigger('change'); 
                    // clientSelect.prop('required', false);
                }
            }

            // Panggil saat halaman load & saat tipe berubah
            toggleClientSelection();
            typeSelect.on('change', toggleClientSelection);
        });
    </script>
@endpush