{{-- 1. Beranda --}}
<div class="menu_title flex">
    <span class="title">Beranda</span>
    <span class="line"></span>
</div>
<ul class="menu_item">
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
            {{-- Icon diubah menjadi 'home' --}}
            <i class="material-icons">home</i>
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
            {{-- Icon diubah menjadi 'add_shopping_cart' --}}
            <i class="material-icons">add_shopping_cart</i>
            <span>Buat Pesanan Online</span>
        </a>
    </li>
     <li class="item">
         {{-- ✅ Ganti route & routeIs --}}
        <a class="link flex {{ request()->routeIs('client.client-orders.index') || request()->routeIs('client.client-orders.show') ? 'active' : '' }}" href="{{ route('client.client-orders.index') }}">
            {{-- Icon diubah menjadi 'receipt_long' --}}
            <i class="material-icons">receipt_long</i>
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
            {{-- Icon diubah menjadi 'inventory_2' (cocok dengan admin) --}}
            <i class="material-icons">inventory_2</i>
            <span>Riwayat Pesanan (Sales)</span>
        </a>
    </li>
    <li class="item">
        {{-- ✅ KODE DI BAWAH INI DIPERBARUI --}}
        <a class="link flex {{ (request()->routeIs('client.invoices.*') && !request()->routeIs('client.invoices.batchPay.*')) ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
            <i class="material-icons">receipt</i>
            <span>Riwayat Invoice</span>
        </a>
    </li>
    <li class="item">
        <a class="link flex {{ request()->routeIs('client.invoices.batchPay.*') ? 'active' : '' }}" href="{{ route('client.invoices.batchPay.create') }}">
            {{-- Icon diubah menjadi 'payments' (cocok dengan admin) --}}
            <i class="material-icons">payments</i>
            <span>Pembayaran Tagihan</span>
        </a>
    </li>
</ul>