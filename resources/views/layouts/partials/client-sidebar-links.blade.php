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

{{-- 2. Pemesanan Online --}}
<div class="menu_title flex">
    <span class="title">Pemesanan Online</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        {{-- ✅ Ganti route & routeIs --}}
        <a class="link flex {{ request()->routeIs('client.client-orders.create') ? 'active' : '' }}" href="{{ route('client.client-orders.create') }}">
            <i class="bx bxs-cart-add"></i>
            <span>Buat Pesanan Online</span>
        </a>
    </li>
     <li class="item">
         {{-- ✅ Ganti route & routeIs --}}
        <a class="link flex {{ request()->routeIs('client.client-orders.index') || request()->routeIs('client.client-orders.show') ? 'active' : '' }}" href="{{ route('client.client-orders.index') }}">
            <i class='bx bx-clipboard'></i>
            <span>Pesanan Online Saya</span>
        </a>
    </li>
</ul>

{{-- 3. Riwayat Transaksi --}}
<div class="menu_title flex">
    <span class="title">Riwayat Transaksi</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
         {{-- Riwayat Pesanan yang dibuat oleh Sales/Admin --}}
        <a class="link flex {{ request()->routeIs('client.sales-orders.index') || request()->routeIs('client.sales-orders.show') || request()->routeIs('client.sales-orders.requestChange.*') ? 'active' : '' }}" href="{{ route('client.sales-orders.index') }}">
            <i class="bx bxs-box"></i>
            <span>Riwayat Pesanan (Sales)</span>
        </a>
    </li>
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
            <i class="bx bx-receipt"></i>
            <span>Riwayat Invoice</span>
        </a>
    </li>
</ul>