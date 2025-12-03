@extends('admin.layouts.app')

@section('title', 'Kelola Satuan')

@section('content')
<div class="max-w-5xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kelola Satuan</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar satuan pengukuran (UOM) untuk produk.</p>
        </div>
        <div>
            <a href="{{ route('admin.units.create') }}" 
               class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                <i class="material-icons text-[20px]">add</i> Tambah Satuan
            </a>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-16">No.</th>
                        <th>Nama Satuan</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="pl-6 py-4 text-slate-500 text-sm">
                                {{ $loop->iteration + $units->firstItem() - 1 }}
                            </td>
                            <td class="py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                        <i class="material-icons text-[18px]">straighten</i>
                                    </div>
                                    <span class="font-bold text-slate-700">{{ $unit->name }}</span>
                                </div>
                            </td>
                            <td class="py-4 text-center pr-6">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.units.edit', $unit->unit_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-colors shadow-sm"
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    {{-- Delete (Cukup berikan class 'delete-form') --}}
                                    <form action="{{ route('admin.units.destroy', $unit->unit_id) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        {{-- Tambahkan data-name untuk teks konfirmasi --}}
                                        <button type="submit" 
                                                data-name="{{ $unit->name }}"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 transition-colors shadow-sm"
                                                title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">straighten</i>
                                    </div>
                                    <h3 class="text-lg font-medium text-slate-900">Belum ada data</h3>
                                    <p class="text-slate-500 text-sm mt-1 max-w-xs">Mulai dengan menambahkan satuan baru untuk produk Anda.</p>
                                    <a href="{{ route('admin.units.create') }}" class="mt-4 text-indigo-600 font-bold text-sm hover:underline">Tambah Satuan Baru</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $units->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Hanya logika Toast Session saja yang tersisa disini
    // Logika Delete sudah pindah otomatis ke app.js
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
</script>
@endpush