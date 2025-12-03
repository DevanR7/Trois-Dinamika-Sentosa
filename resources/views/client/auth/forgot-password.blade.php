@extends('client.layouts.guest')

@section('content')

    <div class="text-center mb-8">
        <h4 class="text-xl font-bold text-[#0f172a] dark:text-white">Lupa Password?</h4>
        <p class="text-xs text-slate-500 mt-1">Masukkan email Anda. Kami akan mengirimkan link reset.</p>
    </div>

    @if (session('status'))
        <div class="flex p-3 mb-6 text-xs text-green-700 bg-green-50 rounded-lg border border-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 items-center animate-enter">
            <i class="material-icons text-sm mr-2">check_circle</i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="flex p-3 mb-6 text-xs text-red-700 bg-red-50 rounded-lg border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 items-center animate-enter">
            <i class="material-icons text-sm mr-2">error</i>
            <span class="font-semibold">{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('client.password.email') }}" class="space-y-6">
        @csrf

        <div>
            <label class="block mb-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Email Address</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="material-icons text-slate-400 group-focus-within:text-[#0f172a] dark:group-focus-within:text-white transition-colors duration-300 text-[20px]">email</i>
                </div>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg focus:ring-2 focus:ring-[#0f172a]/20 focus:border-[#0f172a] block w-full pl-12 p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:border-indigo-500 transition-all duration-300 placeholder-slate-400"
                    placeholder="Masukkan Email Terdaftar">
            </div>
        </div>

        <button type="submit" class="w-full text-white bg-[#0f172a] hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-bold rounded-lg text-sm px-5 py-3.5 text-center shadow-lg hover:shadow-xl transform transition-all duration-300 active:scale-[0.98] dark:bg-indigo-600 dark:hover:bg-indigo-700">
            KIRIM LINK RESET
        </button>

        <div class="text-center">
            <a href="{{ route('client.login') }}" class="text-xs font-bold text-slate-500 hover:text-[#0f172a] dark:hover:text-indigo-400 flex items-center justify-center gap-1 transition-colors">
                <i class="material-icons text-[14px]">arrow_back</i>
                Kembali ke Login
            </a>
        </div>
    </form>
@endsection