@extends('admin.layouts.app')

@section('title', 'Bagan Akun (COA)')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Bagan Akun (Chart of Accounts)</h1>
                <p class="page-subtitle">Kelola daftar akun akuntansi untuk pelaporan keuangan.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.chart-of-accounts.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-1">add</i> Tambah Akun
                </a>
            </div>
        </div>

        {{-- Alert Info --}}
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500 p-4 rounded-r shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="material-icons text-indigo-500">info</i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">
                        Akun yang ditandai sebagai <strong>Induk (Parent)</strong> tidak dapat digunakan dalam transaksi jurnal. Hanya akun anak (Sub-akun) yang dapat dipilih.
                    </p>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th class="w-32">Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Kategori</th>
                            <th class="text-center">Posisi Normal</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parentAccounts as $parent)
                            {{-- BARIS PARENT --}}
                            <tr class="bg-slate-50/80 dark:bg-slate-800/80 font-semibold">
                                <td class="font-mono text-indigo-700 dark:text-indigo-400">
                                    {{ $parent->account_number }}
                                </td>
                                <td class="text-slate-800 dark:text-white uppercase tracking-wide text-xs">
                                    {{ $parent->account_name }}
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $parent->account_type }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-xs font-mono font-bold">{{ $parent->normal_balance == 'Debit' ? 'Dr' : 'Cr' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($parent->is_active)
                                        <i class="material-icons text-emerald-500 text-[18px]" title="Aktif">check_circle</i>
                                    @else
                                        <i class="material-icons text-slate-400 text-[18px]" title="Non-Aktif">cancel</i>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.table-actions 
                                            edit="{{ route('admin.chart-of-accounts.edit', $parent->account_id) }}"
                                            delete="{{ route('admin.chart-of-accounts.destroy', $parent->account_id) }}"
                                            message="Menghapus akun induk akan gagal jika masih memiliki sub-akun."
                                        />
                                    </div>
                                </td>
                            </tr>

                            {{-- BARIS CHILDREN (LOOP) --}}
                            @foreach($parent->children as $child)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="font-mono text-slate-600 dark:text-slate-400 pl-8 border-l-4 border-l-transparent hover:border-l-indigo-500">
                                        {{ $child->account_number }}
                                    </td>
                                    <td class="pl-8">
                                        <div class="flex items-center gap-2">
                                            <i class="material-icons text-slate-300 text-[16px] rotate-90" style="transform: rotate(90deg) translateY(-2px);">subdirectory_arrow_right</i>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $child->account_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($child->account_type) {
                                                'Aset' => 'badge-info',
                                                'Liabilitas' => 'badge-warning',
                                                'Ekuitas' => 'badge-primary',
                                                'Pendapatan' => 'badge-success',
                                                'HPP', 'Beban' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $child->account_type }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-xs font-mono">{{ $child->normal_balance == 'Debit' ? 'Dr' : 'Cr' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($child->is_active)
                                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" title="Aktif"></span>
                                        @else
                                            <span class="inline-block w-2 h-2 rounded-full bg-slate-300" title="Non-Aktif"></span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <x-ui.table-actions 
                                                edit="{{ route('admin.chart-of-accounts.edit', $child->account_id) }}"
                                                delete="{{ route('admin.chart-of-accounts.destroy', $child->account_id) }}"
                                                message="Apakah Anda yakin menghapus akun ini? Pastikan tidak ada jurnal terkait."
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <p class="text-slate-500 italic">Belum ada data akun. Silakan tambah akun baru.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection