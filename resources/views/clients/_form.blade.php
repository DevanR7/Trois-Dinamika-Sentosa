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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    {{-- KOLOM KIRI: INFO & KONTAK --}}
    <div class="space-y-8">
        <div>
            <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wide mb-4 border-b border-indigo-100 pb-2 flex items-center gap-2">
                <i class="material-icons text-indigo-500 text-base">apartment</i> Informasi Perusahaan
            </h3>
            <div class="space-y-4">
                <div>
                    <label for="client_name">Nama Klien / Perusahaan <span class="text-red-500">*</span></label>
                    <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $client->client_name ?? '') }}" 
                        class="form-input font-medium text-slate-800" 
                        placeholder="PT. Contoh Sejahtera" required>
                    @error('client_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="person_in_charge">Penanggung Jawab (PIC)</label>
                    <input type="text" name="person_in_charge" id="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge ?? '') }}" 
                        class="form-input" 
                        placeholder="Nama Lengkap PIC">
                    @error('person_in_charge') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wide mb-4 border-b border-indigo-100 pb-2 flex items-center gap-2">
                <i class="material-icons text-indigo-500 text-base">location_on</i> Kontak & Alamat
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $client->email ?? '') }}" 
                            class="form-input" 
                            placeholder="email@domain.com">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone_number">No. Telepon / WA</label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $client->phone_number ?? '') }}" 
                            class="form-input" 
                            placeholder="0812...">
                        @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="address">Alamat Lengkap</label>
                    <textarea name="address" id="address" rows="3" 
                        class="form-textarea" 
                        placeholder="Jalan, Kota, Kode Pos...">{{ old('address', $client->address ?? '') }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN: KEAMANAN (LOGIN) --}}
    <div>
        <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 h-full">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                <i class="material-icons text-slate-500 text-base">vpn_key</i> Akun Portal Klien
            </h3>
            
            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 text-xs text-blue-700 mb-6 flex gap-2">
                <i class="material-icons text-sm mt-0.5">info</i> 
                <div>
                    @if(isset($client))
                        Kosongkan password jika tidak ingin mengubahnya.
                    @else
                        Password ini akan digunakan klien untuk login ke Portal Client.
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="password">Password @if(!isset($client))<span class="text-red-500">*</span>@endif</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" {{ isset($client) ? '' : 'required' }}
                            class="form-input pr-10" placeholder="******">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                            <i class="material-icons text-lg">visibility_off</i>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation">Konfirmasi Password @if(!isset($client))<span class="text-red-500">*</span>@endif</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                            class="form-input pr-10" placeholder="******">
                        <button type="button" id="toggle-password-confirmation" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                            <i class="material-icons text-lg">visibility_off</i>
                        </button>
                    </div>
                    <p id="password-match-error" class="text-red-600 text-xs mt-1 hidden flex items-center gap-1 font-bold">
                        <i class="material-icons text-[14px]">cancel</i> Password tidak cocok.
                    </p>
                    @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>
    
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password Toggle
    function setupToggle(inputId, btnId) {
        const input = document.getElementById(inputId);
        const btn = document.getElementById(btnId);
        if(!input || !btn) return;
        
        btn.addEventListener('click', () => {
            const icon = btn.querySelector('i');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            icon.innerText = type === 'password' ? 'visibility_off' : 'visibility';
        });
    }
    setupToggle('password', 'toggle-password');
    setupToggle('password_confirmation', 'toggle-password-confirmation');

    // Password Match
    const p1 = document.getElementById('password');
    const p2 = document.getElementById('password_confirmation');
    const err = document.getElementById('password-match-error');

    if(p1 && p2 && err) {
        const checkMatch = () => {
            if (p2.value && p1.value !== p2.value) {
                err.classList.remove('hidden');
            } else {
                err.classList.add('hidden');
            }
        };
        p1.addEventListener('input', checkMatch);
        p2.addEventListener('input', checkMatch);
    }
});
</script>
@endpush