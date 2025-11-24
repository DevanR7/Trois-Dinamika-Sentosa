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

{{-- SECTION 1: INFORMASI LOGIN --}}
<div class="mb-6">
    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Informasi Login & Peran</h6>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div>
            <label for="full_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
            <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name ?? '') }}" required>
        </div>

        <div>
            <label for="role" class="block text-xs font-bold text-gray-500 uppercase mb-1">Peran (Role) <span class="text-red-500">*</span></label>
            <select class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="role" name="role" required>
                <option value="" disabled selected>Pilih Role...</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" 
                        @if(isset($user)) @selected($user->hasRole($role->name)) @endif>
                        {{ Str::title($role->name) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="username" class="block text-xs font-bold text-gray-500 uppercase mb-1">Username <span class="text-red-500">*</span></label>
            <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required>
        </div>

        <div>
            <label for="email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        {{-- Kode Sales (Conditional) --}}
        <div id="sales-code-container" style="display: none;">
            <label for="sales_code" class="block text-xs font-bold text-gray-500 uppercase mb-1">Kode Sales</label>
            <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="sales_code" name="sales_code" value="{{ old('sales_code', $user->sales_code ?? '') }}" placeholder="Contoh: KV">
        </div>
    </div>
</div>

{{-- SECTION 2: INFORMASI TAMBAHAN --}}
<div class="mb-6">
    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Informasi Tambahan</h6>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="nik" class="block text-xs font-bold text-gray-500 uppercase mb-1">NIK (Opsional)</label>
            <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="nik" name="nik" value="{{ old('nik', $user->nik ?? '') }}">
        </div>
        
        <div>
            <label for="phone_number" class="block text-xs font-bold text-gray-500 uppercase mb-1">No. Telepon (Opsional)</label>
            <input type="text" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}">
        </div>

        <div class="md:col-span-2">
            <label for="address" class="block text-xs font-bold text-gray-500 uppercase mb-1">Alamat (Opsional)</label>
            <textarea class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" id="address" name="address" rows="2">{{ old('address', $user->address ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- SECTION 3: KEAMANAN --}}
<div>
    <h6 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Keamanan</h6>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- Password --}}
        <div>
            <label for="password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Password @if(!isset($user))<span class="text-red-500">*</span>@endif</label>
            <div class="relative rounded-md shadow-sm">
                <input type="password" id="password" name="password" class="form-input block w-full rounded-lg border-gray-300 pr-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" {{ isset($user) ? '' : 'required' }}>
                <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600">
                    <i class="material-icons text-lg">visibility_off</i>
                </button>
            </div>
            @if(isset($user))<p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah password.</p>@endif
        </div>

        {{-- Konfirmasi Password --}}
        <div>
            <label for="password_confirmation" class="block text-xs font-bold text-gray-500 uppercase mb-1">Konfirmasi Password @if(!isset($user))<span class="text-red-500">*</span>@endif</label>
            <div class="relative rounded-md shadow-sm">
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input block w-full rounded-lg border-gray-300 pr-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" {{ isset($user) ? '' : 'required' }}>
                <button type="button" id="toggle-password-confirmation" class="absolute inset-y-0 right-0 px-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600">
                    <i class="material-icons text-lg">visibility_off</i>
                </button>
            </div>
            <p id="password-match-error" class="mt-1 text-xs text-red-600 hidden flex items-center gap-1">
                <i class="material-icons text-xs">error</i> Password tidak cocok.
            </p>
        </div>
    </div>
</div>