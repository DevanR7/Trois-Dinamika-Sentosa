@if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
        <i class="material-icons text-red-500 text-lg mt-0.5">error</i>
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
        <label for="title" class="block text-xs font-bold text-gray-500 uppercase mb-1">Judul (Opsional)</label>
        <input type="text" name="title" id="title" value="{{ old('title', $announcement->title ?? '') }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Pemberitahuan Libur Lebaran">
    </div>

    {{-- Konten --}}
    <div>
        <label for="content" class="block text-xs font-bold text-gray-500 uppercase mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
        <textarea name="content" id="content" rows="5" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required placeholder="Tulis isi pengumuman di sini...">{{ old('content', $announcement->content ?? '') }}</textarea>
    </div>

    {{-- Tipe --}}
    <div>
        <label for="type" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Pengumuman <span class="text-red-500">*</span></label>
        <select name="type" id="type" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
            <option value="broadcast" @selected(old('type', $announcement->type ?? 'broadcast') == 'broadcast')>Broadcast (Semua Klien)</option>
            <option value="targeted" @selected(old('type', $announcement->type ?? '') == 'targeted')>Targeted (Klien Tertentu)</option>
        </select>
    </div>

    {{-- Pilihan Klien (Conditional) --}}
    <div id="client-selection-container" style="display: {{ old('type', $announcement->type ?? 'broadcast') == 'targeted' ? 'block' : 'none' }};">
        <label for="client_ids" class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Klien Target <span id="client-required-indicator" class="text-red-500 hidden">*</span></label>
        <select class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="client_ids" name="client_ids[]" multiple="multiple">
            @foreach ($clients as $client)
                <option value="{{ $client->client_id }}" 
                    @selected(in_array($client->client_id, old('client_ids', $selectedClientIds ?? [])))> 
                    {{ $client->client_name }} ({{ $client->email ?? '-' }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Pilih satu atau lebih klien yang akan menerima pengumuman ini.</p>
        @error('client_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Status Switch --}}
    <div class="pt-2 border-t border-gray-100">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $announcement->is_active ?? false)) class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer">
            </div>
            <div class="ml-3 text-sm">
                <label for="is_active" class="font-medium text-gray-700 cursor-pointer">Publikasikan Sekarang?</label>
                <p class="text-gray-500 text-xs">Jika dicentang, pengumuman akan langsung tampil di portal klien.</p>
            </div>
        </div>
    </div>

</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Style Select2 agar mirip input Tailwind */
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #d1d5db !important;
            border-radius: 0.5rem;
            padding: 0.3rem 0.5rem;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 1px #6366f1 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih klien...',
                allowClear: true,
                width: '100%'
            });

            // Logic Tipe Pengumuman
            const typeSelect = $('#type');
            const clientContainer = $('#client-selection-container');
            const clientRequiredIndicator = $('#client-required-indicator');
            const clientSelect = $('#client_ids');

            function toggleClientSelection() {
                if (typeSelect.val() === 'targeted') {
                    clientContainer.slideDown();
                    clientRequiredIndicator.removeClass('hidden');
                } else {
                    clientContainer.slideUp();
                    clientRequiredIndicator.addClass('hidden');
                    clientSelect.val(null).trigger('change'); 
                }
            }

            toggleClientSelection();
            typeSelect.on('change', toggleClientSelection);
        });
    </script>
@endpush