@extends('client.layouts.guest')

@section('content')

    {{-- Text Header Mobile --}}
    <div class="lg:hidden text-center mb-8">
        {{-- ID Secret Trigger ditempel di teks ini --}}
        <h4 class="text-xl font-bold text-[#0f172a] dark:text-white select-none cursor-default" id="secret-trigger-mobile">Client Portal</h4>
        <p class="text-xs text-slate-500">Silakan login untuk melanjutkan</p>
    </div>

    {{-- Text Header Desktop --}}
    <div class="hidden lg:block mb-8 text-center">
        <h2 class="text-3xl font-bold text-[#0f172a] dark:text-white mb-2">Selamat Datang</h2>
        <p class="text-sm text-slate-500">Masukkan akun klien Anda untuk melanjutkan</p>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="flex p-3 mb-6 text-xs text-green-700 bg-green-50 rounded-lg border border-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 items-center animate-enter">
            <i class="material-icons text-sm mr-2">check_circle</i>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="flex p-3 mb-6 text-xs text-red-700 bg-red-50 rounded-lg border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 items-center animate-enter">
            <i class="material-icons text-sm mr-2">error</i>
            <span class="font-semibold">{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('client.login') }}" class="space-y-5">
        @csrf

        {{-- Username/Email --}}
        <div>
            <label for="email" class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Email / Username</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 ease-in-out text-[20px]">person</i>
                </div>
                <input type="text" name="email" id="email" 
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 ease-in-out placeholder-slate-400" 
                    placeholder="Masukkan Email Anda" required autofocus value="{{ old('email') }}">
            </div>
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Password</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 ease-in-out text-[20px]">lock</i>
                </div>
                <input type="password" name="password" id="password" 
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 pr-16 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 ease-in-out placeholder-slate-400" 
                    placeholder="Masukkan Password" required>
                <div id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-4 cursor-pointer text-[10px] font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 uppercase tracking-wider select-none transition-colors duration-300">
                    SHOW
                </div>
            </div>
        </div>

        {{-- Options --}}
        <div class="flex items-center justify-between mt-1">
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-[#0f172a] bg-slate-100 border-slate-300 rounded focus:ring-[#0f172a] dark:bg-slate-700 dark:border-slate-600 cursor-pointer transition-all duration-300">
                <label for="remember" class="ml-2 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none">Ingat saya</label>
            </div>
            @if (Route::has('client.password.request'))
                <a href="{{ route('client.password.request') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 transition-colors duration-300">
                    Lupa password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" id="btn-login" class="w-full text-white bg-[#0f172a] hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-bold rounded-lg text-sm px-5 py-3.5 text-center shadow-lg hover:shadow-xl transform transition-all duration-300 active:scale-[0.98] dark:bg-indigo-600 dark:hover:bg-indigo-700 flex justify-center items-center gap-2">
            <span class="btn-text">MASUK</span>
            <svg class="btn-loader hidden w-5 h-5 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>

        {{-- Separator --}}
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200 dark:border-slate-700"></div></div>
            <div class="relative flex justify-center text-sm"><span class="px-2 bg-white dark:bg-[#151f32] text-slate-400 text-[10px] uppercase font-bold tracking-wider">Atau</span></div>
        </div>

        {{-- Google --}}
        <a href="{{ route('client.auth.google') }}" class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:border-slate-300 dark:bg-transparent dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 transition-all duration-300 gap-2 shadow-sm">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
            <span>Masuk dengan Google</span>
        </a>

        {{-- Link Daftar --}}
        <div class="text-center mt-6 pt-2">
            <p class="text-xs text-slate-400">
                Belum punya akun? 
                <a href="{{ route('client.register') }}" class="font-bold text-[#0f172a] hover:underline dark:text-indigo-400 transition-colors duration-300">Daftar Sekarang</a>
            </p>
        </div>

    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Password Toggle
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerText = (type === 'text') ? 'HIDE' : 'SHOW';
                });
            }

            // 2. Button Loading
            const form = document.querySelector('form');
            const btnLogin = document.getElementById('btn-login');
            const btnText = btnLogin.querySelector('.btn-text');
            const btnLoader = btnLogin.querySelector('.btn-loader');
            if(form) {
                form.addEventListener('submit', function() {
                    btnLogin.disabled = true;
                    btnLogin.classList.add('opacity-75', 'cursor-not-allowed');
                    btnText.innerText = 'MEMPROSES...';
                    btnLoader.classList.remove('hidden');
                });
            }

            // 3. Secret Trigger Logic (Redirect ke Admin)
            let clickCount = 0;
            let clickTimer = null;
            const staffLoginUrl = "{{ route('admin.login') }}";

            function handleSecretClick() {
                clickCount++;
                if (clickTimer) clearTimeout(clickTimer);
                clickTimer = setTimeout(() => { clickCount = 0; }, 1000);
                if (clickCount >= 5) {
                    document.body.style.cursor = 'wait';
                    window.location.href = staffLoginUrl;
                }
            }

            const mobileTrigger = document.getElementById('secret-trigger-mobile');
            const desktopLogo = document.querySelector('.login-logo');
            const desktopTitle = document.querySelector('.welcome-title');

            if (mobileTrigger) mobileTrigger.addEventListener('click', handleSecretClick);
            if (desktopLogo) desktopLogo.addEventListener('click', handleSecretClick);
            if (desktopTitle) desktopTitle.addEventListener('click', handleSecretClick);
        });
    </script>
@endpush