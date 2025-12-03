{{-- 1. Beranda --}}
<div class="mb-2">
    <div class="menu_title">
        <span>Beranda</span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>
</div>

{{-- 2. Pemesanan Online --}}
<div class="mb-2">
    <div class="menu_title">
        <span>Pemesanan</span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link {{ request()->routeIs('client.client-orders.create') ? 'active' : '' }}" href="{{ route('client.client-orders.create') }}">
                <i class="material-icons">add_shopping_cart</i>
                <span>Buat Pesanan Baru</span>
            </a>
        </li>
        <li class="item">
            <a class="link {{ request()->routeIs('client.client-orders.index') || request()->routeIs('client.client-orders.show') ? 'active' : '' }}" href="{{ route('client.client-orders.index') }}">
                <i class="material-icons">receipt_long</i>
                <span>Pesanan Saya</span>
            </a>
        </li>
    </ul>
</div>

{{-- 3. Riwayat Transaksi --}}
<div class="mb-6">
    <div class="menu_title">
        <span>Riwayat & Tagihan</span>
    </div>
    <ul class="menu_item">
        <li class="item">
            <a class="link {{ request()->routeIs('client.sales-orders.index') || request()->routeIs('client.sales-orders.show') || request()->routeIs('client.sales-orders.requestChange.*') ? 'active' : '' }}" href="{{ route('client.sales-orders.index') }}">
                <i class="material-icons">inventory_2</i>
                <span>Riwayat Order (Sales)</span>
            </a>
        </li>
        <li class="item">
            <a class="link {{ (request()->routeIs('client.invoices.*') && !request()->routeIs('client.invoices.batchPay.*')) ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
                <i class="material-icons">receipt</i>
                <span>Riwayat Invoice</span>
            </a>
        </li>
        <li class="item">
            <a class="link {{ request()->routeIs('client.invoices.bulkPay.*') ? 'active' : '' }}" href="{{ route('client.invoices.bulkPay.create') }}">
                <i class="material-icons">payments</i>
                <span>Pembayaran Tagihan</span>
            </a>
        </li>
    </ul>
</div>