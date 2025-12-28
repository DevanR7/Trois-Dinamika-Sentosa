@extends('admin.layouts.app')

@section('title', 'Permintaan Perubahan Order')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Permintaan Perubahan Order</h1>
                <p class="page-subtitle">Daftar permintaan pembatalan atau revisi pesanan dari klien.</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.order-change-requests.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Search --}}
                <div class="md:col-span-1">
                    <label class="form-label">Pencarian</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="material-icons text-sm">search</i></span>
                        <input type="text" name="search" class="form-input" 
                               placeholder="No. Req, Order, atau Klien..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div>
                    <label class="form-label">Periode</label>
                    <select name="date_filter" class="tom-select">
                        <option value="">Semua Periode</option>
                        @foreach($uniqueDates as $ym => $label)
                            <option value="{{ $ym }}" {{ request('date_filter') == $ym ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="form-label">Tipe Request</label>
                    <select name="type_filter" class="tom-select">
                        <option value="">Semua Tipe</option>
                        <option value="cancel" {{ request('type_filter') == 'cancel' ? 'selected' : '' }}>Pembatalan (Cancel)</option>
                        <option value="modify" {{ request('type_filter') == 'modify' ? 'selected' : '' }}>Revisi (Modify)</option>
                    </select>
                </div>

                {{-- Submit --}}
                <div class="flex items-end">
                    <button type="submit" class="btn btn-secondary w-full">
                        Filter Data
                    </button>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Request</th>
                            <th>No. Order</th>
                            <th>Klien</th>
                            <th>Tipe</th>
                            <th>Tanggal Request</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequests as $req)
                            <tr>
                                <td class="font-bold">
                                    #{{ $req->request_id }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.sales-orders.show', $req->order_id) }}" class="text-indigo-600 hover:underline">
                                        {{ $req->order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $req->client->client_name }}
                                    </div>
                                </td>
                                <td>
                                    @if($req->request_type == 'cancel')
                                        <span class="badge badge-danger">
                                            <i class="material-icons text-[10px] mr-1">cancel</i> Pembatalan
                                        </span>
                                    @else
                                        <span class="badge badge-info">
                                            <i class="material-icons text-[10px] mr-1">edit</i> Revisi
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $req->created_at->format('d M Y') }}
                                    <div class="text-[10px] text-muted">{{ $req->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    @if($req->status == 'pending')
                                        <span class="badge badge-warning">Menunggu</span>
                                    @elseif($req->status == 'approved')
                                        <span class="badge badge-success">Disetujui</span>
                                    @else
                                        <span class="badge badge-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.order-change-requests.show', $req->request_id) }}" 
                                       class="btn btn-sm {{ $req->status == 'pending' ? 'btn-primary' : 'btn-secondary' }}">
                                        {{ $req->status == 'pending' ? 'Proses' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="material-icons text-4xl text-slate-300 mb-2">inbox</i>
                                        <span class="text-muted">Tidak ada permintaan perubahan ditemukan.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $changeRequests->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection