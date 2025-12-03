@if ($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm animate-enter">
        <h3 class="text-sm font-bold text-red-800 flex items-center gap-2">
            <i class="material-icons text-red-600 text-xl">error_outline</i> Terdapat kesalahan input:
        </h3>
        <ul class="mt-2 list-disc list-inside text-xs text-red-700">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="space-y-8">
    
    {{-- Nama Role --}}
    <div>
        <label for="name" class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Role <span class="text-red-500">*</span></label>
        <input type="text" class="form-input block w-full md:w-1/2 font-medium text-slate-800" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" placeholder="Contoh: Staff Gudang" required>
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Permissions --}}
    <div class="border-t border-slate-100 pt-6">
        <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-4 flex items-center gap-2">
            <i class="material-icons text-base">security</i> Hak Akses (Permissions)
        </h6>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($permissions as $group => $permissionList)
                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden flex flex-col h-full hover:shadow-sm transition-shadow">
                    <div class="bg-slate-50/80 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                        <h6 class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                            {{ Str::title(str_replace(['-', '_'], ' ', $group)) }}
                        </h6>
                        <span class="text-[10px] font-bold bg-white border border-slate-200 text-slate-400 px-1.5 py-0.5 rounded">{{ count($permissionList) }}</span>
                    </div>
                    <div class="p-4 flex-grow">
                        <div class="space-y-2">
                            @foreach($permissionList as $permission)
                                <label class="flex items-start cursor-pointer group select-none">
                                    <div class="relative flex items-center pt-0.5">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                               class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-slate-300 transition-all checked:border-indigo-600 checked:bg-indigo-600 focus:ring-2 focus:ring-indigo-100 group-hover:border-indigo-400"
                                               @checked(in_array($permission->name, old('permissions', $roleHasPermissions ?? [])))>
                                        <i class="material-icons absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[12px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none">check</i>
                                    </div>
                                    <span class="ml-2.5 text-sm text-slate-600 group-hover:text-indigo-700 transition-colors">
                                        {{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>