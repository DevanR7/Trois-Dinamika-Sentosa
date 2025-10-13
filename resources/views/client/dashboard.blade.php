@extends('layouts.client') {{-- Gunakan layout baru --}}

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Dashboard</h2>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Total Piutang</div>
                    {{-- Logic for this will be added to the controller later --}}
                    <h4 class="fw-bold mb-0">Rp 0</h4> 
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Pesanan Aktif</div>
                    <h4 class="fw-bold mb-0">0</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">5 Invoice Terbaru</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    {{-- (The table from your old dashboard.blade.php is correct here) --}}
                </table>
            </div>
        </div>
    </div>
</div>
@endsection