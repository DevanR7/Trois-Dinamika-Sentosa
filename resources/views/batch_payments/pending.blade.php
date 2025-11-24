@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Verifikasi Pembayaran</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar pembayaran batch yang menunggu konfirmasi (Pending).</p>
        </div>
        {{-- Spacer / Filter Button jika diperlukan --}}
    </div>

    {{-- LIST CONTAINER --}}
    <div class="space-y-4">
        @forelse ($pendingBatches as $batch)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                        
                        {{-- Kolom 1: Info Utama (Icon & Klien) - Span 5 --}}
                        <div class="md:col-span-5 flex items-center gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center">
                                <i class="material-icons text-2xl">pending_actions</i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900 leading-tight">
                                    {{ $batch->client->client_name ?? 'N/A' }}
                                </h4>
                                <div class="flex items-center gap-1 text-xs text-gray-500 mt-1">
                                    <i class="material-icons text-[14px]">schedule</i>
                                    <span>{{ $batch->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Metode & Total - Span 4 --}}
                        <div class="md:col-span-4 border-l border-gray-100 pl-0 md:pl-4 md:border-l">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Metode & Nominal</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $batch->paymentMethod->name ?? 'N/A' }}
                                </span>
                                <span class="text-lg font-bold text-indigo-600">
                                    Rp {{ number_format($batch->total_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- Kolom 3: Aksi - Span 3 --}}
                        <div class="md:col-span-3 flex justify-start md:justify-end">
                            <a href="{{ route('batch-payments.showPending', $batch->batch_payment_id) }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <span>Proses</span>
                                <i class="material-icons text-sm ml-2">arrow_forward</i>
                            </a>
                        </div>

                    </div>
                </div>
                
                {{-- Optional Footer / Bar (misal warna status) --}}
                <div class="h-1 w-full bg-yellow-400"></div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-300 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                    <i class="material-icons text-3xl text-gray-400">check_circle</i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak Ada Data Pending</h3>
                <p class="text-gray-500 max-w-sm mt-1">Semua pembayaran batch telah diproses atau belum ada data masuk.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $pendingBatches->links() }}
    </div>
</div>
@endsection