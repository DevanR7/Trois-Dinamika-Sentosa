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

<div class="mb-8">
    <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Role <span class="text-red-500">*</span></label>
    <input type="text" class="form-input block w-full md:w-1/2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" placeholder="Contoh: Staff Gudang" required>
</div>

<div class="border-t border-gray-200 pt-6">
    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Hak Akses (Permissions)</h6>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($permissions as $group => $permissionList)
            <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden flex flex-col h-full">
                <div class="bg-white px-4 py-3 border-b border-gray-200">
                    <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wide">
                        {{ Str::title(str_replace('_', ' ', $group)) }}
                    </h6>
                </div>
                <div class="p-4 flex-grow">
                    <div class="space-y-3">
                        @foreach($permissionList as $permission)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="perm-{{$permission->id}}" 
                                           name="permissions[]" 
                                           type="checkbox" 
                                           value="{{ $permission->name }}"
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer"
                                           @checked(in_array($permission->name, old('permissions', $roleHasPermissions ?? [])))
                                    >
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="perm-{{$permission->id}}" class="font-medium text-gray-700 cursor-pointer select-none">
                                        {{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>