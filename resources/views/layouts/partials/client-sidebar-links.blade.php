{{-- Section Utama --}}
<li class="nav-heading">Utama</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
        <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
    </a>
</li>

{{-- Section Riwayat --}}
<li class="nav-heading mt-3">Riwayat</li>
<li class="nav-item">
    <a class="nav-link" href="#">
        <i class="bi bi-box-seam me-2"></i> Riwayat Pesanan
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="#">
        <i class="bi bi-receipt me-2"></i> Riwayat Invoice
    </a>
</li>