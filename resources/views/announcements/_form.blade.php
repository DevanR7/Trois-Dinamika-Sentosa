@if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3 shadow-sm">
        <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
        <div>
            <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
            <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="space-y-6">
    
    {{-- Judul --}}
    <div>
        <label for="title">Judul (Opsional)</label>
        <input type="text" name="title" id="title" value="{{ old('title', $announcement->title ?? '') }}" class="form-input" placeholder="Contoh: Pemberitahuan Libur Lebaran">
    </div>

    {{-- Konten --}}
    <div>
        <label for="content">Isi Pengumuman <span class="text-red-500">*</span></label>
        <textarea name="content" id="content" rows="5" class="form-textarea" required placeholder="Tulis isi pengumuman di sini...">{{ old('content', $announcement->content ?? '') }}</textarea>
    </div>

    {{-- Tipe --}}
    <div>
        <label for="type">Tipe Pengumuman <span class="text-red-500">*</span></label>
        {{-- Mengganti form-select lama dengan form-input/select2 style --}}
        <select name="type" id="type" class="form-input" required>
            <option value="broadcast" @selected(old('type', $announcement->type ?? 'broadcast') == 'broadcast')>Broadcast (Semua Klien)</option>
            <option value="targeted" @selected(old('type', $announcement->type ?? '') == 'targeted')>Targeted (Klien Tertentu)</option>
        </select>
    </div>

    {{-- Pilihan Klien (Conditional) --}}
    <div id="client-selection-container" style="display: {{ old('type', $announcement->type ?? 'broadcast') == 'targeted' ? 'block' : 'none' }};">
        <label for="client_ids">Pilih Klien Target <span id="client-required-indicator" class="text-red-500 hidden">*</span></label>
        
        {{-- Menggunakan class select2-basic agar styling dari app.css/libraries terbaca --}}
        <select class="select2-basic form-input" id="client_ids" name="client_ids[]" multiple="multiple">
            @foreach ($clients as $client)
                <option value="{{ $client->client_id }}" 
                    @selected(in_array($client->client_id, old('client_ids', $selectedClientIds ?? [])))> 
                    {{ $client->client_name }} ({{ $client->email ?? '-' }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Pilih satu atau lebih klien yang akan menerima pengumuman ini.</p>
        @error('client_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Status Switch --}}
    <div class="pt-4 border-t border-slate-100">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $announcement->is_active ?? false)) class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
            </div>
            <div class="ml-3 text-sm">
                <label for="is_active" class="font-medium text-slate-700 cursor-pointer">Publikasikan Sekarang?</label>
                <p class="text-slate-500 text-xs">Jika dicentang, pengumuman akan langsung tampil di portal klien.</p>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    {{-- Memastikan Select2 diinisialisasi untuk multiple selection --}}
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('.select2-basic').select2({
                placeholder: 'Pilih klien...',
                allowClear: true,
                width: '100%'
            });

            // Logic Tipe Pengumuman
            const typeSelect = $('#type');
            const clientContainer = $('#client-selection-container');
            const clientSelect = $('#client_ids');

            function toggleClientSelection() {
                // Gunakan slideToggle untuk animasi sederhana
                if (typeSelect.val() === 'targeted') {
                    clientContainer.slideDown(200);
                    clientSelect.prop('required', true); 
                } else {
                    clientContainer.slideUp(200);
                    clientSelect.prop('required', false);
                    clientSelect.val(null).trigger('change'); 
                }
            }

            toggleClientSelection();
            typeSelect.on('change', toggleClientSelection);
        });
    </script>
@endpush