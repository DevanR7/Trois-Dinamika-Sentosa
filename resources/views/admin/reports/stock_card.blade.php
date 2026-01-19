@extends('admin.layouts.app')

@section('title', 'Kartu Stok')

@section('content')
    {{-- Header --}}
    <div class="page-header print:hidden">
        <div>
            <h1 class="page-title">Kartu Stok</h1>
            <p class="page-subtitle">Laporan mutasi persediaan dengan saldo berjalan (Stock Card)</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="material-icons text-[18px]">print</i>
                Cetak Kartu Stok
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-6 print:hidden">
        <div class="card-body">
            <form action="{{ route('admin.reports.stock-card') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                
                {{-- Pilih Produk --}}
                <div class="md:col-span-2">
                    <label class="form-label">Pilih Produk</label>
                    <select name="product_id" class="tom-select" placeholder="Cari produk...">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->product_id }}" 
                                {{ request('product_id') == $product->product_id ? 'selected' : '' }}>
                                {{ $product->product_name }} ({{ $product->product_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Mulai --}}
                <div>
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-input" 
                           value="{{ $startDate }}">
                </div>

                {{-- Tanggal Selesai & Button --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-input" 
                               value="{{ $endDate }}">
                    </div>
                    <button type="submit" class="btn btn-primary mb-[1px]">
                        <i class="material-icons">filter_list</i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Report Content --}}
    @if($selectedProduct)
        
        {{-- Header Cetak (Hanya muncul saat print) --}}
        <div class="hidden print:block mb-6 border-b border-black pb-4">
            <h2 class="text-xl font-bold uppercase">Kartu Stok Barang</h2>
            <p class="text-sm">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="card p-4 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <p class="text-xs font-bold text-slate-500 uppercase">Saldo Awal</p>
                <p class="text-lg font-bold text-slate-700 dark:text-slate-200 mt-1 font-mono">
                    {{ number_format($openingStock, 0, ',', '.') }}
                </p>
            </div>
            <div class="card p-4 border border-emerald-100 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800">
                <p class="text-xs font-bold text-emerald-600 uppercase">Total Masuk</p>
                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400 mt-1 font-mono">
                    +{{ number_format(collect($stockCardData)->sum('in'), 0, ',', '.') }}
                </p>
            </div>
            <div class="card p-4 border border-rose-100 bg-rose-50 dark:bg-rose-900/20 dark:border-rose-800">
                <p class="text-xs font-bold text-rose-600 uppercase">Total Keluar</p>
                <p class="text-lg font-bold text-rose-700 dark:text-rose-400 mt-1 font-mono">
                    -{{ number_format(collect($stockCardData)->sum('out'), 0, ',', '.') }}
                </p>
            </div>
            <div class="card p-4 border-l-4 border-indigo-500 bg-white dark:bg-slate-800 shadow-sm">
                <p class="text-xs font-bold text-indigo-500 uppercase">Saldo Akhir</p>
                <p class="text-xl font-bold text-indigo-700 dark:text-indigo-400 mt-1 font-mono">
                    {{ number_format($endingStock, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Stock Card Table --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern w-full">
                    <thead>
                        <tr>
                            <th class="w-32">Tanggal</th>
                            <th>No. Referensi</th>
                            <th>Keterangan</th>
                            <th class="text-right w-24">Masuk</th>
                            <th class="text-right w-24">Keluar</th>
                            <th class="text-right w-24 bg-slate-50 dark:bg-slate-700/50">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Baris Saldo Awal --}}
                        <tr class="bg-slate-50/50 dark:bg-slate-800/30">
                            <td class="font-medium text-slate-500">
                                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                            </td>
                            <td>-</td>
                            <td class="font-bold text-slate-500 italic">Saldo Awal (Bawaan)</td>
                            <td class="text-right text-slate-400">-</td>
                            <td class="text-right text-slate-400">-</td>
                            <td class="text-right font-bold font-mono bg-slate-50 dark:bg-slate-700/50">
                                {{ number_format($openingStock, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Loop Transaksi --}}
                        @forelse($stockCardData as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="font-mono text-xs text-slate-600 dark:text-slate-300">
                                        {{ $row->reference }}
                                    </span>
                                </td>
                                <td class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $row->description }}
                                </td>
                                <td class="text-right font-mono text-emerald-600">
                                    @if($row->in > 0)
                                        {{ number_format($row->in, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right font-mono text-rose-600">
                                    @if($row->out > 0)
                                        {{ number_format($row->out, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right font-bold font-mono bg-slate-50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                                    {{ number_format($row->balance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            @if($openingStock == 0)
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-slate-400">
                                        Tidak ada pergerakan stok pada periode ini.
                                    </td>
                                </tr>
                            @endif
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        {{-- Empty State Awal --}}
        <div class="card p-12 text-center">
            <div class="w-20 h-20 bg-indigo-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-500">
                <i class="material-icons text-4xl">topic</i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 dark:text-white mb-1">Kartu Stok</h3>
            <p class="text-slate-500 text-sm">Pilih produk dan rentang tanggal untuk melihat kartu stok.</p>
        </div>
    @endif
@endsection