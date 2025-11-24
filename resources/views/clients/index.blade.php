@extends('layouts.app')

@section('title', 'Kelola Klien')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Kelola Klien</h2>
            <p class="text-sm text-gray-500 mt-1">Manajemen data pelanggan dan mitra bisnis.</p>
        </div>
        <div class="flex gap-3">
            @if(request('status') === 'deleted')
                <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                    <i class="bi bi-arrow-left mr-2"></i> Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('clients.index', ['status' => 'deleted']) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                    <i class="bi bi-archive mr-2"></i> Arsip
                </a>
                <a href="{{ route('clients.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                    <i class="bi bi-plus-lg mr-2"></i> Tambah Klien
                </a>
            @endif
        </div>
    </div>

    {{-- CARD LIST --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Klien</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">PIC</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Saldo Kredit</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clients as $client)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $client->client_name }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $client->email ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $client->phone_number ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($client->person_in_charge)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    {{ $client->person_in_charge }}
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($client->trashed())
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Arsip</span>
                            @elseif($client->is_locked)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-800 text-white border border-gray-600 uppercase"><i class="bi bi-lock-fill mr-1"></i> Terkunci</span>
                            @elseif($client->is_approved)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Aktif</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-sm text-gray-900">
                            @if($client->balance > 0)
                                <span class="text-green-600 font-bold">Rp {{ number_format($client->balance, 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center items-center gap-1">
                                @if($client->trashed())
                                    {{-- Action Deleted --}}
                                    <a href="{{ route('clients.show', $client->client_id) }}" class="p-1.5 rounded hover:bg-gray-100 text-indigo-600 transition" title="Lihat"><i class="bi bi-eye"></i></a>
                                    <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-restore inline-block">
                                        @csrf @method('PATCH')
                                        <button type="button" class="p-1.5 rounded hover:bg-green-50 text-green-600 transition btn-restore" data-name="{{ $client->client_name }}" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                @else
                                    {{-- Action Active --}}
                                    
                                    {{-- Approve --}}
                                    @if(!$client->is_approved)
                                        <form action="{{ route('clients.approve', $client->client_id) }}" method="POST" class="form-approve inline-block">
                                            @csrf @method('PATCH')
                                            <button type="button" class="p-1.5 rounded hover:bg-green-50 text-green-600 transition btn-approve" data-name="{{ $client->client_name }}" title="Setujui"><i class="bi bi-check-lg text-lg"></i></button>
                                        </form>
                                    @endif

                                    {{-- Lock --}}
                                    @if($client->is_locked)
                                        <form action="{{ route('clients.unlock', $client->client_id) }}" method="POST" class="form-unlock inline-block">
                                            @csrf @method('PATCH')
                                            <button type="button" class="p-1.5 rounded hover:bg-yellow-50 text-yellow-600 transition btn-unlock" data-name="{{ $client->client_name }}" title="Buka Kunci"><i class="bi bi-unlock-fill"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('clients.lock', $client->client_id) }}" method="POST" class="form-lock inline-block">
                                            @csrf @method('PATCH')
                                            <button type="button" class="p-1.5 rounded hover:bg-gray-100 text-gray-500 transition btn-lock" data-name="{{ $client->client_name }}" title="Kunci Akun"><i class="bi bi-lock"></i></button>
                                        </form>
                                    @endif

                                    <a href="{{ route('clients.show', $client->client_id) }}" class="p-1.5 rounded hover:bg-indigo-50 text-indigo-600 transition" title="Lihat"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('clients.edit', $client->client_id) }}" class="p-1.5 rounded hover:bg-yellow-50 text-yellow-600 transition" title="Edit"><i class="bi bi-pencil-square"></i></a>

                                    <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-delete inline-block">
                                        @csrf @method('DELETE')
                                        <button type="button" class="p-1.5 rounded hover:bg-red-50 text-red-600 transition btn-delete" data-name="{{ $client->client_name }}" title="Arsipkan"><i class="bi bi-archive"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="bi bi-people text-xl text-gray-400"></i>
                                </div>
                                <p class="text-sm">Tidak ada data klien ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $clients->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Helper Konfirmasi SweetAlert
    const confirmAction = (selector, title, text, btnColor, btnText) => {
        document.querySelectorAll(selector).forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                const name = this.dataset.name;
                Swal.fire({
                    title: title,
                    text: text.replace(':name', name),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: btnColor,
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: btnText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    };

    confirmAction('.btn-approve', 'Setujui Klien?', 'Anda akan menyetujui klien ":name".', '#10b981', 'Ya, Setujui!');
    confirmAction('.btn-lock', 'Kunci Akun?', 'Klien ":name" tidak akan bisa login.', '#1f2937', 'Ya, Kunci!');
    confirmAction('.btn-unlock', 'Buka Kunci?', 'Akses login klien ":name" akan dibuka.', '#f59e0b', 'Ya, Buka!');
    confirmAction('.btn-delete', 'Arsipkan Klien?', 'Klien ":name" akan diarsipkan.', '#ef4444', 'Ya, Arsipkan!');
    confirmAction('.btn-restore', 'Pulihkan Klien?', 'Klien ":name" akan dipulihkan.', '#10b981', 'Ya, Pulihkan!');

    // Toast Notifications
    @if(session('success'))
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000 });
    @endif
    @if(session('error'))
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ session('error') }}', showConfirmButton: false, timer: 5000 });
    @endif
});
</script>
@endpush