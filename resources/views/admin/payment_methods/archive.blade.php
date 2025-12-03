@extends('admin.layouts.app')

@section('title', 'Arsip Metode Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Arsip Metode</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar metode pembayaran yang telah dinonaktifkan.</p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali ke Aktif
        </a>
    </div>

    {{-- TABEL ARSIP --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Nama Metode</th>
                        <th>Tipe</th>
                        <th>Tanggal Arsip</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($archivedMethods as $method)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4">
                                <span class="text-sm font-bold text-slate-500 group-hover:text-slate-700 transition-colors">
                                    {{ $method->name }}
                                </span>
                            </td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wide">
                                    {{ $method->type }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-slate-500">
                                {{ $method->deleted_at->format('d M Y H:i') }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    
                                    {{-- Restore (Menggunakan class .form-confirm agar ditangkap app.js) --}}
                                    <form action="{{ route('admin.payment-methods.archived.restore', $method->payment_method_id) }}" method="POST" class="form-confirm inline-block">
                                        @csrf
                                        <button type="submit" 
                                                data-title="Pulihkan Metode?" 
                                                data-text="Metode <b>{{ $method->name }}</b> akan kembali aktif." 
                                                data-btn-text="Ya, Pulihkan" 
                                                data-btn-color="#10b981" 
                                                data-icon="question"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm" 
                                                title="Pulihkan">
                                            <i class="material-icons text-[16px]">restore</i>
                                        </button>
                                    </form>
                                    
                                    {{-- Force Delete (Menggunakan class .delete-form agar ditangkap app.js) --}}
                                    <form action="{{ route('admin.payment-methods.archived.forceDelete', $method->payment_method_id) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ $method->name }}" 
                                                data-title="Hapus Permanen?"
                                                data-text="Data akan hilang selamanya dan <b>tidak bisa dikembalikan!</b>"
                                                data-btn-text="Ya, Hapus Permanen"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition shadow-sm" 
                                                title="Hapus Permanen">
                                            <i class="material-icons text-[16px]">delete_forever</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">archive</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Arsip Kosong</h3>
                                    <p class="text-sm mt-1">Tidak ada metode pembayaran yang diarsipkan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection