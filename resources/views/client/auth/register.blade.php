@extends('client.layouts.guest')

@section('content')

    {{-- Mobile Header --}}
    <div class="lg:hidden text-center mb-8">
        <h4 class="text-xl font-bold text-[#0f172a] dark:text-white">Daftar Akun</h4>
        <p class="text-xs text-slate-500">Isi data di bawah untuk mendaftar</p>
    </div>

    {{-- Desktop Header --}}
    <div class="hidden lg:block mb-8 text-center">
        <h2 class="text-3xl font-bold text-[#0f172a] dark:text-white mb-2">Buat Akun Baru</h2>
        <p class="text-sm text-slate-500">Lengkapi formulir di bawah ini</p>
    </div>

    {{-- Alert Error --}}
    @if ($errors->any())
        <div class="flex p-3 mb-6 text-xs text-red-700 bg-red-50 rounded-lg border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 animate-enter">
            <i class="material-icons text-sm mr-2">error</i>
            <div>
                <strong class="block mb-1">Gagal Mendaftar</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('client.register') }}" class="space-y-4">
        @csrf

        {{-- Nama Perusahaan / Klien --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Nama Perusahaan / Klien</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">business</i>
                </div>
                <input type="text" name="client_name" value="{{ old('client_name') }}" required autofocus
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Masukkan Nama Perusahaan">
            </div>
        </div>

        {{-- Email --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Alamat Email</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">email</i>
                </div>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Masukkan Email Aktif">
            </div>
        </div>

        {{-- Password --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Password</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">lock</i>
                </div>
                <input type="password" name="password" id="password" required
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 pr-16 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Buat Password Baru">
                
                <div id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-4 cursor-pointer text-[10px] font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 uppercase tracking-wider select-none transition-colors duration-300">
                    SHOW
                </div>
            </div>
        </div>

        {{-- Konfirmasi Password --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Konfirmasi Password</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">lock_clock</i>
                </div>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Ulangi Password Baru">
            </div>
            
            <div id="password-match-error" class="text-red-500 text-xs mt-1 hidden flex items-center">
                <i class="material-icons mr-1 text-[14px]">warning</i>
                Password tidak cocok.
            </div>
        </div>

        {{-- Button Register --}}
        <button type="submit" class="w-full text-white bg-[#0f172a] hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-bold rounded-lg text-sm px-5 py-3.5 text-center shadow-lg hover:shadow-xl transform transition-all duration-300 active:scale-[0.98] dark:bg-indigo-600 dark:hover:bg-indigo-700 mt-4">
            DAFTAR SEKARANG
        </button>

        {{-- Link Kembali --}}
        <div class="text-center mt-6 pt-2 border-t border-slate-100 dark:border-slate-700">
            <p class="text-xs text-slate-400">
                Sudah punya akun? 
                <a href="{{ route('client.login') }}" class="font-bold text-[#0f172a] hover:underline dark:text-indigo-400 transition-colors duration-300">Masuk di sini</a>
            </p>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle Password
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerText = (type === 'text') ? 'HIDE' : 'SHOW';
                });
            }

            // Validasi Password Cocok
            const passConfInput = document.getElementById('password_confirmation');
            const passError = document.getElementById('password-match-error');

            if (passwordInput && passConfInput && passError) {
                function validate() {
                    if (passwordInput.value !== passConfInput.value && passConfInput.value.length > 0) {
                        passError.classList.remove('hidden');
                        passConfInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                    } else {
                        passError.classList.add('hidden');
                        passConfInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                    }
                }
                passwordInput.addEventListener('input', validate);
                passConfInput.addEventListener('input', validate);
            }
        });
    </script>
@endpush