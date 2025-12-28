@extends('client.layouts.guest')

@section('content')

    {{-- HEADER TEXT --}}
    <div class="text-center mb-8">
        {{-- Mobile Secret Trigger --}}
        <div class="lg:hidden mb-2">
            <h4 class="text-xl font-extrabold text-[#0f172a] dark:text-white tracking-tight select-none cursor-pointer" id="secret-trigger-mobile">
                Client Portal
            </h4>
        </div>
        
        <h2 class="text-2xl lg:text-3xl font-bold text-[#0f172a] dark:text-white mb-2 tracking-tight">
            Selamat Datang
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Masukkan kredensial akun klien Anda
        </p>
    </div>

    {{-- ALERT NOTIFICATIONS --}}
    <div class="space-y-4 mb-6">
        {{-- 1. Success --}}
        @if (session('success'))
            <div class="flex items-start p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300 animate-enter" role="alert">
                <i class="material-icons text-base mr-2 mt-0.5">check_circle</i>
                <span class="font-medium leading-relaxed">{{ session('success') }}</span>
            </div>
        @endif

        {{-- 2. Error (Locked / Invalid / Generic) --}}
        @if (session('error'))
            <div class="flex items-start p-4 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-300 animate-enter" role="alert">
                <i class="material-icons text-base mr-2 mt-0.5">error</i>
                <span class="font-medium leading-relaxed">{{ session('error') }}</span>
            </div>
        @endif

        {{-- 3. Warning (Pending Approval) --}}
        @if (session('warning'))
            <div class="flex items-start p-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300 animate-enter" role="alert">
                <i class="material-icons text-base mr-2 mt-0.5">warning</i>
                <span class="font-medium leading-relaxed">{{ session('warning') }}</span>
            </div>
        @endif

        {{-- 4. Validation Errors (Laravel Default) --}}
        @if ($errors->any())
            <div class="flex items-start p-4 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-300 animate-enter" role="alert">
                <i class="material-icons text-base mr-2 mt-0.5">block</i>
                <div class="flex-1">
                    <span class="font-bold block mb-1">Terjadi Kesalahan:</span>
                    <ul class="list-disc list-inside space-y-1 ml-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- LOGIN FORM --}}
    <form method="POST" action="{{ route('client.login') }}" class="space-y-6" id="login-form">
        @csrf

        {{-- Input: Email --}}
        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider ml-1">
                Email Address
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-[20px] text-slate-400 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400 transition-colors duration-300">email</i>
                </div>
                <input type="email" name="email" id="email" 
                    class="block w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:placeholder-slate-500 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400" 
                    placeholder="nama@perusahaan.com" required autofocus value="{{ old('email') }}">
            </div>
        </div>

        {{-- Input: Password --}}
        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider ml-1">
                Password
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-[20px] text-slate-400 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400 transition-colors duration-300">lock</i>
                </div>
                <input type="password" name="password" id="password" 
                    class="block w-full pl-12 pr-12 py-3.5 bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 dark:bg-slate-800 dark:border-slate-700 dark:placeholder-slate-500 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400" 
                    placeholder="••••••••" required>
                
                {{-- Toggle Show/Hide --}}
                <div id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-4 cursor-pointer group/eye">
                    <i class="material-icons text-[20px] text-slate-400 group-hover/eye:text-indigo-600 transition-colors" id="eyeIcon">visibility_off</i>
                </div>
            </div>
        </div>

        {{-- Options: Remember & Forgot Password --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" 
                    class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600 transition-all cursor-pointer">
                <label for="remember" class="ml-2 text-sm font-medium text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                    Ingat saya
                </label>
            </div>
            @if (Route::has('client.password.request'))
                <a href="{{ route('client.password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                    Lupa Password?
                </a>
            @endif
        </div>

        {{-- Submit Button --}}
        <button type="submit" id="btn-login" class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-[#0f172a] hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 shadow-lg hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="material-icons text-slate-400 group-hover:text-white transition-colors">login</i>
            </span>
            <span class="btn-text tracking-wide">MASUK KE PORTAL</span>
            
            {{-- Loading Spinner --}}
            <svg class="btn-loader hidden w-5 h-5 ml-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>

        {{-- Divider --}}
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white dark:bg-[#151f32] text-slate-400 text-xs font-bold uppercase tracking-wider">
                    Atau Masuk Dengan
                </span>
            </div>
        </div>

        {{-- Google Login --}}
        <a href="{{ route('client.auth.google') }}" class="flex items-center justify-center w-full px-4 py-3.5 text-sm font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 dark:bg-slate-800/50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 transition-all duration-300 gap-3 shadow-sm group">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5 transition-transform group-hover:scale-110">
            <span>Lanjutkan dengan Google</span>
        </a>

        {{-- Register Link --}}
        <div class="text-center pt-2">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Belum menjadi klien kami? 
                <a href="{{ route('client.register') }}" class="font-bold text-[#0f172a] hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors underline decoration-2 decoration-transparent hover:decoration-current">
                    Daftar Sekarang
                </a>
            </p>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Password Toggle (Icon based)
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (toggleBtn && passwordInput && eyeIcon) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    eyeIcon.innerText = (type === 'text') ? 'visibility' : 'visibility_off';
                });
            }

            // 2. Button Loading Animation
            const form = document.getElementById('login-form');
            const btnLogin = document.getElementById('btn-login');
            const btnText = btnLogin.querySelector('.btn-text');
            const btnLoader = btnLogin.querySelector('.btn-loader');

            if(form) {
                form.addEventListener('submit', function() {
                    // Prevent double submit visual
                    btnLogin.classList.add('cursor-not-allowed', 'opacity-90');
                    btnText.innerText = 'MEMPROSES...';
                    btnLoader.classList.remove('hidden');
                    // Note: Jangan disable button jika form invalid (browser handle ini), 
                    // tapi jika validasi HTML5 lolos, form akan submit.
                });
            }

            // 3. Secret Trigger Logic (Redirect to Admin Login)
            let clickCount = 0;
            let clickTimer = null;
            const staffLoginUrl = "{{ route('admin.login') }}";

            function handleSecretClick() {
                clickCount++;
                if (clickTimer) clearTimeout(clickTimer);
                clickTimer = setTimeout(() => { clickCount = 0; }, 1000); // Reset after 1 sec
                
                if (clickCount >= 5) {
                    document.body.style.cursor = 'wait';
                    // Optional: Visual feedback
                    const title = document.querySelector('.welcome-title');
                    if(title) title.style.color = '#ef4444'; 
                    
                    window.location.href = staffLoginUrl;
                }
            }

            const mobileTrigger = document.getElementById('secret-trigger-mobile');
            const desktopLogo = document.querySelector('.login-logo');
            const desktopTitle = document.querySelector('.welcome-title'); // Dari layout guest

            if (mobileTrigger) mobileTrigger.addEventListener('click', handleSecretClick);
            if (desktopLogo) desktopLogo.addEventListener('click', handleSecretClick);
            if (desktopTitle) desktopTitle.addEventListener('click', handleSecretClick);
        });
    </script>
@endpush