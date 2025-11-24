@extends('layouts.app')

@section('title', 'Pengaturan Pajak')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Pengaturan Pajak</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar semua tarif pajak yang berlaku (PPN, PPh, dll).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('taxes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Tarif
            </a>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-green-500">check_circle</i>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500">error</i>
            <span class="text-sm text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- TABEL --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/2">
                            Nama Pajak
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Tarif (%)
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($taxes as $tax)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                {{ $tax->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded border border-gray-200">
                                    {{ number_format($tax->rate, 2, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($tax->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                        Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="{{ route('taxes.edit', $tax->id) }}" class="inline-flex p-1.5 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition border border-gray-200 shadow-sm hover:border-yellow-200" title="Edit">
                                    <i class="material-icons text-lg leading-none">edit</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">percent</i>
                                    <p class="text-base font-medium">Belum ada data</p>
                                    <p class="text-sm mt-1">Silakan tambahkan tarif pajak baru.</p>
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