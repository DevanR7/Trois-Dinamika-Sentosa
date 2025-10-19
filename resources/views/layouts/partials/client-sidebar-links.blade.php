{{-- Section Utama --}}
<li class="nav-heading">Utama</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
        <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('client.orders.create') ? 'active' : '' }}" href="{{ route('client.orders.create') }}">
        <i class="bi bi-cart-plus-fill me-2"></i> Buat Pesanan Baru
    </a>
</li>

{{-- Section Riwayat --}}
<li class="nav-heading mt-3">Riwayat</li>
<li class="nav-item">
    {{-- ✅ BERUBAH: Tambahkan pengecualian untuk create --}}
    <a class="nav-link {{ request()->routeIs('client.orders.index') || request()->routeIs('client.orders.show') ? 'active' : '' }}" href="{{ route('client.orders.index') }}">
        <i class="bi bi-box-seam me-2"></i> Riwayat Pesanan
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
        <i class="bi bi-receipt me-2"></i> Riwayat Invoice
    </a>
</li>
<li class="nav-heading mt-3">Akun</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('client.profile.edit') ? 'active' : '' }}" href="{{ route('client.profile.edit') }}">
        <i class="bi bi-person-circle me-2"></i> Profil Saya
    </a>
</li>