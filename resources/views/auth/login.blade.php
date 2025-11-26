@extends('layouts.admin-guest')

@section('content')

    {{-- 
        PERBAIKAN:
        1. max-w-4xl: Ukuran card lebih ramping (sebelumnya 5xl).
        2. min-h-[500px]: Tinggi disesuaikan agar tidak terlalu memanjang ke bawah.
    --}}
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col lg:flex-row w-full max-w-4xl min-h-[500px] transition-all duration-300 border border-slate-100 relative z-20">
        
        {{-- BAGIAN KIRI: Welcome Section (Gelap) --}}
        <div class="hidden lg:flex flex-col justify-between p-10 w-full lg:w-[45%] bg-[#0f172a] text-white relative overflow-hidden">
            
            {{-- Background Ornament --}}
            <div class="absolute top-[-50px] left-[-50px] w-32 h-32 bg-indigo-500 rounded-full opacity-20 blur-3xl"></div>
            <div class="absolute bottom-[-20px] right-[-20px] w-48 h-48 bg-blue-600 rounded-full opacity-20 blur-3xl"></div>

            {{-- Logo & Intro --}}
            <div class="relative z-10 mt-4">
                {{-- Pastikan gambar logo Anda transparan/putih --}}
                <img src="{{ asset('images/TDS-side-text.png') }}" alt="Logo" class="w-40 mb-8 brightness-0 invert opacity-90">
                
                {{-- PERBAIKAN: Text color dipaksa putih (text-white) --}}
                <h2 class="text-3xl font-bold mb-2 tracking-tight text-white leading-tight">Internal Portal</h2>
                <p class="text-indigo-200 font-medium mb-4 text-sm">Trois Dinamika Sentosa</p>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Masuk untuk mengelola sistem operasional dan memantau laporan kinerja secara realtime.
                </p>
            </div>

            {{-- WIDGET JAM --}}
            <div id="clock-widget" class="relative z-10 border-t border-slate-700/50 pt-4 mt-8">
                <div class="text-xs text-slate-400 font-medium tracking-wide">Hari ini</div>
                <div class="text-xl font-bold text-white tracking-widest mt-0.5">--:--</div>
                <div class="text-[10px] text-slate-500 mt-1" id="date-text">...</div>
            </div>
        </div>

        {{-- BAGIAN KANAN: Form Section (Putih) --}}
        <div class="w-full lg:w-[55%] p-8 lg:p-10 bg-white flex flex-col justify-center">
            
            {{-- Mobile Logo (Hanya muncul di HP) --}}
            <div class="lg:hidden text-center mb-6">
                <h2 class="text-xl font-bold text-slate-800">Internal Portal</h2>
                <p class="text-xs text-slate-500">Silakan login untuk melanjutkan</p>
            </div>

            {{-- Alert Error --}}
            @if ($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 p-3 rounded-lg flex items-start gap-3">
                    <i class="material-icons text-red-500 text-sm mt-0.5">error</i>
                    <div>
                        <p class="text-xs text-red-700 font-bold">Login Gagal</p>
                        <p class="text-[10px] text-red-600 mt-0.5">{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Input Username --}}
                <div>
                    <label for="username" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Username</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px] group-focus-within:text-indigo-600 transition-colors">person</i>
                        </div>
                        <input id="username" type="text" name="username" 
                            class="pl-10 w-full bg-white text-slate-700 border border-slate-200 rounded-lg py-2.5 px-4 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm placeholder:text-slate-400 hover:border-slate-300"
                            value="{{ old('username') }}" required autofocus placeholder="Masukkan Username">
                    </div>
                </div>

                {{-- Input Password --}}
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px] group-focus-within:text-indigo-600 transition-colors">lock</i>
                        </div>
                        <input id="password" type="password" name="password" required 
                            class="pl-10 pr-10 w-full bg-white text-slate-700 border border-slate-200 rounded-lg py-2.5 px-4 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm placeholder:text-slate-400 hover:border-slate-300"
                            placeholder="Masukkan Password">
                        
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" id="togglePassword">
                            <i class="material-icons text-slate-400 hover:text-slate-600 text-[18px] transition-colors">visibility_off</i>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-1">
                    <div class="flex items-center">
                        <input id="remember" type="checkbox" name="remember" class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember" class="ml-2 block text-xs text-slate-500 cursor-pointer select-none">Ingat saya</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                            Lupa password?
                        </a>
                    @endif
                </div>

                <button type="submit" class="w-full bg-[#0f172a] hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transform active:scale-[0.98] transition-all duration-200 text-sm tracking-wide mt-2">
                    MASUK
                </button>

                {{-- Separator --}}
                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-slate-100"></div>
                    <span class="flex-shrink-0 mx-3 text-[10px] font-medium text-slate-400 uppercase">Atau</span>
                    <div class="flex-grow border-t border-slate-100"></div>
                </div>

                {{-- Google Login --}}
                <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-medium py-2.5 px-4 rounded-lg transition-all duration-200 text-sm group">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-4 h-4" alt="Google">
                    <span>Masuk dengan Google</span>
                </a>
            </form>

            <div class="mt-6 text-center border-t border-slate-50 pt-4">
                <p class="text-[11px] text-slate-400">
                    Klien Eksternal? 
                    <a href="{{ route('client.login') }}" class="font-bold text-indigo-600 hover:underline ml-0.5">Masuk disini</a>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Logic Jam Digital (Realtime & Immediate)
            function updateClock() {
                const now = new Date();
                const clockWidget = document.getElementById('clock-widget');
                
                if (clockWidget) {
                    const timeEl = clockWidget.querySelector('div:nth-child(2)'); // Element Jam
                    const dateEl = document.getElementById('date-text');
                    
                    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    // Format Waktu: 14:30
                    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
                    
                    if(timeEl) timeEl.innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':') + ' WIB';
                    if(dateEl) dateEl.innerText = now.toLocaleDateString('id-ID', dateOptions);
                }
            }
            
            // Panggil segera agar tidak menunggu 1 detik
            updateClock();
            setInterval(updateClock, 1000);

            // 2. Logic Toggle Password
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.textContent = 'visibility';
                        icon.classList.remove('text-slate-400');
                        icon.classList.add('text-indigo-600');
                    } else {
                        icon.textContent = 'visibility_off';
                        icon.classList.add('text-slate-400');
                        icon.classList.remove('text-indigo-600');
                    }
                });
            }
        });
    </script>
@endpush