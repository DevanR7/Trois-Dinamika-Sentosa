@if ($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    {{-- KOLOM KIRI: INFO & KONTAK --}}
    <div class="space-y-6">
        <div>
            <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wide mb-4 border-b border-indigo-100 pb-2">
                Informasi Perusahaan
            </h3>
            <div class="space-y-4">
                <div>
                    <label for="client_name" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Klien / Perusahaan <span class="text-red-500">*</span></label>
                    <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $client->client_name ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                        placeholder="PT. Contoh Sejahtera" required>
                </div>
                <div>
                    <label for="person_in_charge" class="block text-xs font-bold text-gray-700 uppercase mb-1">Penanggung Jawab (PIC)</label>
                    <input type="text" name="person_in_charge" id="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                        placeholder="Nama Lengkap PIC">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wide mb-4 border-b border-indigo-100 pb-2">
                Kontak & Alamat
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 uppercase mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $client->email ?? '') }}" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                            placeholder="email@domain.com">
                    </div>
                    <div>
                        <label for="phone_number" class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Telepon / WA</label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $client->phone_number ?? '') }}" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                            placeholder="0812...">
                    </div>
                </div>
                <div>
                    <label for="address" class="block text-xs font-bold text-gray-700 uppercase mb-1">Alamat Lengkap</label>
                    <textarea name="address" id="address" rows="3" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                        placeholder="Jalan, Kota, Kode Pos...">{{ old('address', $client->address ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN: KEAMANAN (LOGIN) --}}
    <div>
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-2 flex items-center gap-2">
                <i class="bi bi-shield-lock text-indigo-500"></i> Akun Login Portal
            </h3>
            
            <div class="bg-white p-3 rounded-lg border border-gray-200 text-xs text-gray-500 mb-6 italic">
                <i class="bi bi-info-circle me-1"></i> 
                @if(isset($client))
                    Kosongkan password jika tidak ingin mengubahnya.
                @else
                    Password ini akan digunakan klien untuk login ke Portal Client.
                @endif
            </div>

            <div class="space-y-5">
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 uppercase mb-1">Password @if(!isset($client))<span class="text-red-500">*</span>@endif</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" {{ isset($client) ? '' : 'required' }}
                            class="w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-indigo-600">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-gray-700 uppercase mb-1">Konfirmasi Password @if(!isset($client))<span class="text-red-500">*</span>@endif</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                            class="w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <button type="button" id="toggle-password-confirmation" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-indigo-600">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                    <p id="password-match-error" class="text-red-500 text-xs mt-1 hidden font-medium">
                        <i class="bi bi-exclamation-circle"></i> Password tidak cocok.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
</div>