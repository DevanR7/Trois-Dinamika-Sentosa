@extends('client.layouts.guest')

@section('content')

    <div class="text-center mb-8">
        <h4 class="text-xl font-bold text-[#0f172a] dark:text-white">Atur Password Baru</h4>
        <p class="text-xs text-slate-500 mt-1">Silakan buat password baru untuk akun Anda.</p>
    </div>

    @if ($errors->any())
        <div class="flex p-3 mb-6 text-xs text-red-700 bg-red-50 rounded-lg border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 items-center animate-enter">
            <i class="material-icons text-sm mr-2">error</i>
            <span class="font-semibold">{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('client.password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email Readonly --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Email Address</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 text-[20px]">email</i>
                </div>
                <input type="email" name="email" value="{{ $email ?? old('email') }}" required readonly
                    class="bg-slate-100 border border-slate-200 text-slate-500 text-sm rounded-lg block w-full pl-12 p-3 cursor-not-allowed dark:bg-slate-800/50 dark:border-slate-700 dark:text-slate-500">
            </div>
        </div>

        {{-- Password Baru --}}
        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Password Baru</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">lock</i>
                </div>
                <input type="password" name="password" id="password" required
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 pr-16 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Masukkan Password Baru">
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
                <input type="password" name="password_confirmation" required
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Ulangi Password Baru">
            </div>
        </div>

        <button type="submit" class="w-full text-white bg-[#0f172a] hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-bold rounded-lg text-sm px-5 py-3.5 text-center shadow-lg hover:shadow-xl transform transition-all duration-300 active:scale-[0.98] dark:bg-indigo-600 dark:hover:bg-indigo-700 mt-4">
            SIMPAN PASSWORD BARU
        </button>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerText = (type === 'text') ? 'HIDE' : 'SHOW';
            });
        }
    });
</script>
@endpush