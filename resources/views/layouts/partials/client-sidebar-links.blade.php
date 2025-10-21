{{-- 1. Beranda --}}
<div class="menu_title flex">
    <span class="title">Beranda</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
            <i class="bx bx-home-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
</ul>

{{-- 2. Pesanan --}}
<div class="menu_title flex">
    <span class="title">Pesanan</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.orders.create') ? 'active' : '' }}" href="{{ route('client.orders.create') }}">
            <i class="bx bxs-cart-add"></i>
            <span>Buat Pesanan Baru</span>
        </a>
    </li>
    <li class="item">
        {{-- Aktif jika di index atau show, tapi BUKAN create --}}
        <a class="link flex {{ (request()->routeIs('client.orders.index') || request()->routeIs('client.orders.show')) && !request()->routeIs('client.orders.create') ? 'active' : '' }}" href="{{ route('client.orders.index') }}">
            <i class="bx bxs-box"></i>
            <span>Riwayat Pesanan</span>
        </a>
    </li>
</ul>

{{-- 3. Keuangan --}}
<div class="menu_title flex">
    <span class="title">Keuangan</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
            <i class="bx bx-receipt"></i>
            <span>Riwayat Invoice</span>
        </a>
    </li>
</ul>

{{-- Section Akun dihapus karena sudah ada di dropdown bawah --}}