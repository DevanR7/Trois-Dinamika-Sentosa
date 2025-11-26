@if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-enter">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 text-red-500">
                <i class="material-icons text-xl">error</i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

{{-- SECTION 1: INFORMASI UTAMA & LOGIN --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
        <i class="material-icons text-indigo-600 text-[20px]">badge</i>
        <h6 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Informasi Akun</h6>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Nama Lengkap --}}
        <div class="col-span-1 md:col-span-2">
            <label for="full_name">Nama Lengkap <span class="text-red-500">*</span></label>
            <input type="text" class="form-input" id="full_name" name="full_name" 
                   value="{{ old('full_name', $user->full_name ?? '') }}" 
                   placeholder="Masukkan nama lengkap user" required>
            @error('full_name') <p class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="material-icons text-[10px]">error</i> {{ $message }}</p> @enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="username">Username <span class="text-red-500">*</span></label>
            <input type="text" class="form-input" id="username" name="username" 
                   value="{{ old('username', $user->username ?? '') }}" 
                   placeholder="Tanpa spasi" required>
            @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email">Email <span class="text-red-500">*</span></label>
            <input type="email" class="form-input" id="email" name="email" 
                   value="{{ old('email', $user->email ?? '') }}" 
                   placeholder="user@example.com" required>
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Role --}}
        <div class="col-span-1 md:col-span-2">
            <label for="role">Peran (Role) <span class="text-red-500">*</span></label>
            <select class="form-select w-full" id="role" name="role" required>
                <option value="" disabled selected>Pilih Role...</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @if(isset($user) && $user->hasRole($role->name)) selected @endif>
                        {{ Str::title($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        
        {{-- Kode Sales (Kondisional via JS) --}}
        <div id="sales-code-container" class="hidden col-span-1 md:col-span-2 animate-enter">
            <div class="bg-indigo-50/50 p-4 rounded-lg border border-indigo-100">
                <label for="sales_code" class="text-indigo-800">Kode Sales <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-indigo-500 font-bold">#</span>
                    </div>
                    <input type="text" class="form-input pl-8 border-indigo-200 focus:border-indigo-500" 
                           id="sales_code" name="sales_code" 
                           value="{{ old('sales_code', $user->sales_code ?? '') }}" 
                           placeholder="Contoh: KVR01">
                </div>
                <p class="text-[10px] text-indigo-500 mt-1">Wajib diisi jika peran adalah Sales.</p>
            </div>
        </div>
    </div>
</div>

{{-- SECTION 2: KONTAK & DATA PRIBADI --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
        <i class="material-icons text-indigo-600 text-[20px]">contact_mail</i>
        <h6 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Detail Kontak</h6>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- NIK --}}
        <div>
            <label for="nik">NIK (Nomor Induk Kependudukan)</label>
            <input type="text" class="form-input" id="nik" name="nik" 
                   value="{{ old('nik', $user->nik ?? '') }}" placeholder="16 Digit NIK">
        </div>
        
        {{-- No Telepon --}}
        <div>
            <label for="phone_number">No. Telepon / WhatsApp</label>
            <input type="text" class="form-input" id="phone_number" name="phone_number" 
                   value="{{ old('phone_number', $user->phone_number ?? '') }}" placeholder="0812...">
        </div>

        {{-- CONTOH INPUT RUPIAH (Optional - Aktifkan jika ada kolom gaji di DB) --}}
        {{-- 
        <div>
            <label for="salary">Gaji Pokok (Contoh Format Rupiah)</label>
            <input type="text" class="form-input input-currency text-right" id="salary" name="salary" placeholder="0">
        </div>
        --}}

        {{-- Alamat --}}
        <div class="md:col-span-2">
            <label for="address">Alamat Lengkap</label>
            <textarea class="form-textarea w-full" id="address" name="address" rows="3" 
                      placeholder="Jalan, RT/RW, Kelurahan, Kecamatan...">{{ old('address', $user->address ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- SECTION 3: KEAMANAN --}}
<div>
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
        <i class="material-icons text-indigo-600 text-[20px]">lock_reset</i>
        <h6 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Keamanan Password</h6>
    </div>

    <div class="bg-slate-50 border border-slate-100 rounded-lg p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Password --}}
            <div>
                <label for="password">Password Baru @if(!isset($user))<span class="text-red-500">*</span>@endif</label>
                <div class="relative group">
                    <input type="password" id="password" name="password" 
                           class="form-input pr-10" 
                           {{ isset($user) ? '' : 'required' }} placeholder="******">
                    <button type="button" id="toggle-password" 
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none">
                        <i class="material-icons text-lg">visibility_off</i>
                    </button>
                </div>
                @if(isset($user))
                    <p class="mt-1 text-[10px] text-slate-400">Biarkan kosong jika tidak ingin mengganti password.</p>
                @endif
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Konfirmasi Password --}}
            <div>
                <label for="password_confirmation">Ulangi Password @if(!isset($user))<span class="text-red-500">*</span>@endif</label>
                <div class="relative">
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           class="form-input pr-10" 
                           {{ isset($user) ? '' : 'required' }} placeholder="******">
                    <button type="button" id="toggle-password-confirmation" 
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none">
                        <i class="material-icons text-lg">visibility_off</i>
                    </button>
                </div>
                <div id="password-match-indicator" class="mt-1 hidden transition-all duration-300">
                    <p class="text-xs flex items-center gap-1 font-medium">
                        <span class="match-text"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>