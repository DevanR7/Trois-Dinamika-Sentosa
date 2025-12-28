@php
    // Helper function sederhana untuk cek active state (agar code lebih rapi)
    if (!function_exists('isClientActive')) {
        function isClientActive($route) { return request()->routeIs($route) ? 'active' : ''; }
    }
    // Helper untuk cek parent active (jika nanti ada submenu)
    if (!function_exists('isClientParentActive')) {
        function isClientParentActive($routes) {
            foreach ($routes as $route) { if (request()->routeIs($route)) return 'active'; }
            return '';
        }
    }
@endphp

{{-- 1. BERANDA --}}
<div class="px-5 mt-2 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Menu Utama</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-2 mb-2"></div>

<ul class="space-y-1.5">
    <li>
        <a href="{{ route('client.dashboard') }}" class="menu-item {{ isClientActive('client.dashboard') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">dashboard</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Dashboard</div>
        </a>
    </li>
</ul>

{{-- 2. PEMESANAN (Order) --}}
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pemesanan</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    <li>
        <a href="{{ route('client.client-orders.create') }}" class="menu-item {{ isClientActive('client.client-orders.create') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">add_shopping_cart</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Buat Pesanan Baru</div>
        </a>
    </li>
    <li>
        <a href="{{ route('client.client-orders.index') }}" class="menu-item {{ isClientActive('client.client-orders.index') }} {{ isClientActive('client.client-orders.show') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">receipt_long</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Pesanan Saya</div>
        </a>
    </li>
</ul>

{{-- 3. RIWAYAT & TAGIHAN --}}
<div class="px-5 mt-6 mb-2 flex items-center hide-on-collapsed transition-opacity">
    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Keuangan</span>
</div>
<div class="show-on-collapsed h-px w-6 bg-slate-700 mx-auto mt-6 mb-2"></div>

<ul class="space-y-1.5">
    {{-- Sales Orders History --}}
    <li>
        <a href="{{ route('client.sales-orders.index') }}" class="menu-item {{ isClientActive('client.sales-orders.*') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">inventory_2</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Riwayat Order (Sales)</div>
        </a>
    </li>

    {{-- Invoices History --}}
    <li>
        <a href="{{ route('client.invoices.index') }}" class="menu-item {{ (request()->routeIs('client.invoices.*') && !request()->routeIs('client.invoices.bulkPay.*')) ? 'active' : '' }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">receipt</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Riwayat Invoice</div>
        </a>
    </li>

    {{-- Pembayaran --}}
    <li>
        <a href="{{ route('client.invoices.bulkPay.create') }}" class="menu-item {{ isClientActive('client.invoices.bulkPay.*') }}">
            <div class="menu-icon-col">
                <i class="material-icons text-[20px]">payments</i>
            </div>
            <div class="menu-text-col hide-on-collapsed">Pembayaran Tagihan</div>
        </a>
    </li>
</ul>