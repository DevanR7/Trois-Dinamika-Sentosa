@if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
        <i class="material-icons text-red-500 text-lg mt-0.5">error</i>
        <div>
            <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
            <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div>
    <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Satuan <span class="text-red-500">*</span></label>
    <input type="text" 
           class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
           id="name" 
           name="name" 
           value="{{ old('name', $unit->name ?? '') }}" 
           required 
           placeholder="Contoh: Pcs, Box, Kg">
    <p class="mt-1 text-xs text-gray-500">Gunakan nama satuan yang singkat dan jelas (misal: Kg, Ltr, Pcs).</p>
</div>