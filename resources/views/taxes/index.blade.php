@extends("layouts.app")
@section("content")
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Pengaturan Pajak</h2>
            <a href="{{ route("taxes.create") }}" class="btn btn-primary">
                Tambah Tarif Pajak
            </a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Pajak</th>
                            <th>Tarif (%)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($taxes as $tax)
                            <tr>
                                <td>{{ $tax->name }}</td>
                                <td>{{ $tax->rate }}%</td>
                                <td class="text-center">
                                    @if ($tax->is_active)
                                        <span class="badge bg-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a
                                        href="{{ route("taxes.edit", $tax->id) }}"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
