@if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-enter">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 text-red-500 mt-0.5">
                <i class="material-icons text-xl">error_outline</i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="space-y-4">
    <div>
        <label for="name" class="block mb-2 text-xs font-bold text-slate-500 uppercase tracking-wide">
            Nama Satuan <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input type="text" 
                   class="form-input pr-10 focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-slate-300 rounded-md" 
                   id="name" 
                   name="name" 
                   value="{{ old('name', $unit->name ?? '') }}" 
                   required 
                   placeholder="Contoh: Pcs, Box, Kg, Liter">
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                <i class="material-icons text-lg">straighten</i>
            </div>
        </div>
        <p class="mt-1.5 text-[11px] text-slate-500">
            Gunakan singkatan standar jika memungkinkan (misal: Kg untuk Kilogram).
        </p>
        @error('name') 
            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="material-icons text-[14px]">error</i> {{ $message }}
            </p> 
        @enderror
    </div>
</div>