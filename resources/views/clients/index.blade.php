@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola Klien</h2>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Klien Baru
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Klien</th>
                            <th>Email</th>
                            <th>Penanggung Jawab</th>
                            <th>No. Telepon</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                        <tr>
                            <td>{{ $loop->iteration + $clients->firstItem() - 1 }}</td>
                            <td>{{ $client->client_name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->person_in_charge ?? '-' }}</td>
                            <td>{{ $client->phone_number ?? '-' }}</td>
                            <td class="text-center">
        @if($client->is_approved)
            <span class="badge bg-success">Disetujui</span>
        @else
            <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
        @endif
    </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if(!$client->is_approved)
                <form action="{{ route('clients.approve', $client->client_id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                </form>
            @endif
                                    <a href="{{ route('clients.edit', $client->client_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus klien ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">Tidak ada data klien.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $clients->appends(request()->query())->links() }}
    </div>
</div>
@endsection