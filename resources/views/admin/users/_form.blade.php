@if ($errors->any())
    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-r-lg shadow-sm animate-enter">
        <div class="flex items-start gap-3">
            <i class="material-icons text-red-600 dark:text-red-400 text-xl mt-0.5">error_outline</i>
            <div>
                <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-xs text-red-700 dark:text-red-400">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

{{-- SECTION 1: INFORMASI UTAMA --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
        <i class="material-icons text-indigo-600 dark:text-indigo-400 text-[20px]">badge</i>
        <h6 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Informasi Akun</h6>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Nama Lengkap --}}
        <div class="col-span-1 md:col-span-2">
            <label for="full_name" class="form-label label-required">Nama Lengkap</label>
            <input type="text" id="full_name" name="full_name" 
                   value="{{ old('full_name', $user->full_name ?? '') }}" 
                   class="form-input placeholder:text-slate-400" 
                   placeholder="Masukkan nama lengkap user" required>
            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="username" class="form-label label-required">Username</label>
            <input type="text" id="username" name="username" 
                   value="{{ old('username', $user->username ?? '') }}" 
                   class="form-input placeholder:text-slate-400" 
                   placeholder="Tanpa spasi" required>
            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="form-label label-required">Email</label>
            <input type="email" id="email" name="email" 
                   value="{{ old('email', $user->email ?? '') }}" 
                   class="form-input placeholder:text-slate-400" 
                   placeholder="user@example.com" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Role --}}
        <div class="col-span-1 md:col-span-2">
            <label for="role" class="form-label label-required">Peran (Role)</label>
            <select id="role" name="role" class="tom-select-role" placeholder="Pilih Role..." required>
                <option value=""></option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @if(isset($user) && $user->hasRole($role->name)) selected @endif>
                        {{ Str::title($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role') <div class="invalid-feedback block">{{ $message }}</div> @enderror
        </div>
        
        {{-- Kode Sales (Kondisional via JS) --}}
        <div id="sales-code-container" class="hidden col-span-1 md:col-span-2 animate-enter">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-100 dark:border-indigo-800">
                <label for="sales_code" class="form-label label-required text-indigo-800 dark:text-indigo-300">Kode Sales</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-indigo-500 font-bold">#</span>
                    </div>
                    <input type="text" id="sales_code" name="sales_code" 
                           value="{{ old('sales_code', $user->sales_code ?? '') }}" 
                           class="form-input pl-8 border-indigo-200 dark:border-indigo-700 focus:border-indigo-500" 
                           placeholder="Contoh: KVR01">
                </div>
                <p class="form-hint text-indigo-500 dark:text-indigo-400">Wajib diisi jika peran adalah Sales.</p>
                @error('sales_code') <div class="invalid-feedback block">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

{{-- SECTION 2: KONTAK --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
        <i class="material-icons text-indigo-600 dark:text-indigo-400 text-[20px]">contact_mail</i>
        <h6 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Detail Kontak</h6>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- NIK --}}
        <div>
            <label for="nik" class="form-label label-optional">NIK</label>
            <input type="text" id="nik" name="nik" 
                   value="{{ old('nik', $user->nik ?? '') }}" 
                   class="form-input placeholder:text-slate-400 font-mono" 
                   placeholder="16 Digit NIK">
            @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        
        {{-- No Telepon --}}
        <div>
            <label for="phone_number" class="form-label label-optional">No. Telepon / WhatsApp</label>
            <input type="text" id="phone_number" name="phone_number" 
                   value="{{ old('phone_number', $user->phone_number ?? '') }}" 
                   class="form-input placeholder:text-slate-400" 
                   placeholder="0812...">
            @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Alamat --}}
        <div class="md:col-span-2">
            <label for="address" class="form-label label-optional">Alamat Lengkap</label>
            <textarea id="address" name="address" rows="3" 
                      class="form-textarea placeholder:text-slate-400" 
                      placeholder="Jalan, RT/RW, Kelurahan, Kecamatan...">{{ old('address', $user->address ?? '') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

{{-- SECTION 3: KEAMANAN --}}
<div>
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
        <i class="material-icons text-indigo-600 dark:text-indigo-400 text-[20px]">lock_reset</i>
        <h6 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Keamanan Password</h6>
    </div>

    <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Password --}}
            <div>
                <label for="password" class="form-label {{ isset($user) ? 'label-optional' : 'label-required' }}">Password Baru</label>
                <div class="relative group">
                    <input type="password" id="password" name="password" 
                           class="form-input pr-10" 
                           {{ isset($user) ? '' : 'required' }} placeholder="******">
                    <button type="button" id="toggle-password" 
                            class="absolute inset-y-0 right-0 px-3 z-10 flex items-center text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors focus:outline-none">
                        <i class="material-icons text-[18px]">visibility_off</i>
                    </button>
                </div>
                @if(isset($user))
                    <p class="form-hint">Kosongkan jika tidak ingin mengganti password.</p>
                @endif
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Konfirmasi Password --}}
            <div>
                <label for="password_confirmation" class="form-label {{ isset($user) ? 'label-optional' : 'label-required' }}">Ulangi Password</label>
                <div class="relative">
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           class="form-input pr-10" 
                           {{ isset($user) ? '' : 'required' }} placeholder="******">
                    <button type="button" id="toggle-password-confirmation" 
                            class="absolute inset-y-0 right-0 px-3 z-10 flex items-center text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors focus:outline-none">
                        <i class="material-icons text-[18px]">visibility_off</i>
                    </button>
                </div>
                <div id="password-match-indicator" class="mt-2 hidden transition-all duration-300">
                    <p class="text-xs flex items-center gap-1 font-medium match-text"></p>
                </div>
                @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Inisialisasi Tom Select
        let roleTomSelect;
        const roleSelectEl = document.getElementById('role');
        if(roleSelectEl){
            roleTomSelect = new TomSelect(roleSelectEl, window.defaultTomSelectConfig);
        }

        // 2. Logic Kode Sales
        const salesContainer = document.getElementById('sales-code-container');
        
        function checkSalesRole() {
            if(!roleSelectEl) return;
            const val = roleSelectEl.value;
            // Pastikan value ini sesuai dengan yang ada di DB (lowercase biasanya)
            if(val === 'sales' || val === 'salesman') {
                salesContainer.classList.remove('hidden');
            } else {
                salesContainer.classList.add('hidden');
            }
        }
        
        if(roleSelectEl) {
            roleSelectEl.addEventListener('change', checkSalesRole);
            checkSalesRole(); // Cek saat load awal
        }

        // 3. Password Toggle Helpers (Revised & Robust)
        function setupToggle(inputId, btnId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(btnId);
            if(!input || !btn) return;
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility_off';
                }
            });
        }
        setupToggle('password', 'toggle-password');
        setupToggle('password_confirmation', 'toggle-password-confirmation');

        // 4. Check Match Password
        const pass = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const indicator = document.getElementById('password-match-indicator');
        const indicatorText = indicator?.querySelector('.match-text');

        function checkMatch() {
            if(!pass || !confirm || !indicator) return;
            
            if(!confirm.value) { 
                indicator.classList.add('hidden'); 
                return; 
            }
            indicator.classList.remove('hidden');
            
            if(pass.value === confirm.value) {
                indicatorText.innerHTML = '<i class="material-icons text-emerald-500 text-[16px]">check_circle</i> Password Cocok';
                indicatorText.className = 'match-text text-emerald-600 dark:text-emerald-400 flex items-center gap-1';
            } else {
                indicatorText.innerHTML = '<i class="material-icons text-red-500 text-[16px]">cancel</i> Password Tidak Sama';
                indicatorText.className = 'match-text text-red-600 dark:text-red-400 flex items-center gap-1';
            }
        }
        
        if(pass && confirm) {
            pass.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);
        }
    });
</script>
@endpush