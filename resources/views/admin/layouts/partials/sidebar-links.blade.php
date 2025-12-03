@php
    function isParentActive($routes) {
        foreach ($routes as $route) { 
            if (request()->routeIs($route)) return 'active-parent'; 
        }
        return 'hover:bg-[#1a1c29] hover:text-white text-[#b0b3c1]';
    }

    function isActive($route) {
        return request()->routeIs($route)
            ? 'active-parent'
            : 'hover:bg-[#1a1c29] hover:text-white text-[#b0b3c1]';
    }

    function isSubActive($route) { 
        return request()->routeIs($route) ? 'active' : ''; 
    }

    function isGroupOpen($routes) {
        foreach ($routes as $route) { 
            if (request()->routeIs($route)) return ''; 
        }
        return 'hidden';
    }

    function isArrowRotated($routes) {
        foreach ($routes as $route) { 
            if (request()->routeIs($route)) return 'rotate-180'; 
        }
        return '';
    }

    function iconColor($routes) {
        foreach ($routes as $route) { 
            if (request()->routeIs($route)) return 'text-[#5e63ff]'; 
        }
        return 'text-inherit';
    }
@endphp

<ul class="px-2">

    {{-- DASHBOARD --}}
    @can('view-dashboard')
    <li class="menu-item mb-1">
        <a href="{{ route('admin.dashboard') }}" 
           class="menu-link flex items-center px-3 py-3 rounded-lg transition-all duration-200 {{ isActive('admin.dashboard') }}">
            <div class="icon-container w-6 h-6 flex justify-center items-center shrink-0">
                <i class="material-icons text-[22px]">dashboard</i>
            </div>
            <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Dashboard</span>
        </a>
    </li>
    @endcan


    {{-- OPERASIONAL --}}
    @can('view-products')
    <li class="section-header px-3 mt-4 mb-2">
        <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold sidebar-text">Operasional</span>
    </li>

    @php $prodRoutes = ['admin.products.*', 'admin.stock-opnames.*']; @endphp
    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($prodRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <div class="icon-container w-6 h-6 flex justify-center items-center shrink-0">
                    <i class="material-icons text-[20px] {{ iconColor($prodRoutes) }}">inventory_2</i>
                </div>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Produk & Stok</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($prodRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($prodRoutes) }} mt-1" data-label="Produk & Stok">
            <li>
                <a href="{{ route('admin.products.index') }}" 
                   class="submenu-link {{ isSubActive('admin.products.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Daftar Produk</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.stock-opnames.index') }}" 
                   class="submenu-link {{ isSubActive('admin.stock-opnames.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Stock Opname</span>
                </a>
            </li>
        </ul>
    </li>
    @endcan


    {{-- TRANSAKSI --}}
    @if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders', 'view-clients', 'view-sales-orders']))
    <li class="section-header px-3 mt-4 mb-2">
        <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold sidebar-text">Transaksi</span>
    </li>

    {{-- Pembelian --}}
    @if(Auth::user()->canany(['view-suppliers', 'view-purchase-orders']))
    @php 
        $purchRoutes = [
            'admin.suppliers.*', 
            'admin.purchase-orders.*',
            'admin.purchase-order-adjustments.*',
            'admin.bulk-purchase-payments.*',
            'admin.purchase-returns.*'
        ]; 
    @endphp

    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($purchRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <i class="material-icons text-[20px] {{ iconColor($purchRoutes) }}">shopping_cart</i>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Pembelian</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($purchRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($purchRoutes) }} mt-1" data-label="Pembelian">
            @can('view-suppliers')
            <li>
                <a href="{{ route('admin.suppliers.index') }}" class="submenu-link {{ isSubActive('admin.suppliers.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Data Supplier</span>
                </a>
            </li>
            @endcan

            @can('view-purchase-orders')
            <li>
                <a href="{{ route('admin.purchase-orders.index') }}" class="submenu-link {{ isSubActive('admin.purchase-orders.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Pesanan (PO)</span>
                </a>
            </li>
            @endcan

            @can('create-purchase-adjustments')
            <li>
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="submenu-link {{ isSubActive('admin.purchase-order-adjustments.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Penyesuaian PO</span>
                </a>
            </li>
            @endcan

            @can('create-batch-purchase-payments')
            <li>
                <a href="{{ route('admin.bulk-purchase-payments.create') }}" class="submenu-link {{ isSubActive('admin.bulk-purchase-payments.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Bayar Hutang</span>
                </a>
            </li>
            @endcan

            @can('view-purchase-returns')
            <li>
                <a href="{{ route('admin.purchase-returns.index') }}" class="submenu-link {{ isSubActive('admin.purchase-returns.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Retur Pembelian</span>
                </a>
            </li>
            @endcan
        </ul>
    </li>
    @endif


    {{-- Penjualan --}}
    @if(Auth::user()->canany(['view-clients', 'view-sales-orders']))
    @php 
        $salesRoutes = [
            'admin.clients.*',
            'admin.sales-orders.*',
            'admin.client-order-reviews.*',
            'admin.order-change-requests.*',
            'admin.sales-returns.*'
        ];
    @endphp

    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($salesRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <i class="material-icons text-[20px] {{ iconColor($salesRoutes) }}">storefront</i>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Penjualan</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($salesRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($salesRoutes) }} mt-1" data-label="Penjualan">

            @can('view-clients')
            <li>
                <a href="{{ route('admin.clients.index') }}"
                   class="submenu-link {{ isSubActive('admin.clients.*') }}">
                   <div class="dot"></div>
                   <span class="sidebar-text truncate">Data Klien</span>
                </a>
            </li>
            @endcan

            @can('view-sales-orders')
            <li>
                <a href="{{ route('admin.sales-orders.index') }}"
                   class="submenu-link flex justify-between pr-4 {{ isSubActive('admin.sales-orders.*') }}">
                    <div class="flex items-center">
                        <div class="dot"></div>
                        <span class="sidebar-text truncate">Pesanan Sales</span>
                    </div>

                    @if(isset($pendingSalesOrderCount) && $pendingSalesOrderCount > 0)
                        <span class="sidebar-text bg-red-500 text-white text-[10px] px-1.5 rounded-full leading-tight flex items-center justify-center h-4">
                            {{ $pendingSalesOrderCount }}
                        </span>
                    @endif
                </a>
            </li>
            @endcan

            @can('review-client-orders')
            <li>
                <a href="{{ route('admin.client-order-reviews.index') }}"
                   class="submenu-link {{ isSubActive('admin.client-order-reviews.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Review Order</span>
                </a>
            </li>
            @endcan

            @can('review-order-change-requests')
            <li>
                <a href="{{ route('admin.order-change-requests.index') }}"
                   class="submenu-link {{ isSubActive('admin.order-change-requests.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Request Ubah</span>
                </a>
            </li>
            @endcan

            @can('view-sales-returns')
            <li>
                <a href="{{ route('admin.sales-returns.index') }}"
                   class="submenu-link {{ isSubActive('admin.sales-returns.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Retur Jual</span>
                </a>
            </li>
            @endcan

        </ul>
    </li>
    @endif

    @endif



    {{-- KEUANGAN --}}
    @if(Auth::user()->canany(['view-invoices', 'manage-settings']))
    <li class="section-header px-3 mt-4 mb-2">
        <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold sidebar-text">Keuangan</span>
    </li>

    @php 
        $finRoutes = [
            'admin.invoices.*',
            'admin.invoice-adjustments.*',
            'admin.bulk-sales-payments.*',
            'admin.payment-clearance.*'
        ];
    @endphp

    @if(Auth::user()->canany(['view-invoices', 'create-batch-payments']))
    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($finRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <i class="material-icons text-[20px] {{ iconColor($finRoutes) }}">payments</i>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Keuangan</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($finRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($finRoutes) }} mt-1" data-label="Keuangan">

            @can('view-invoices')
            <li>
                <a href="{{ route('admin.invoices.index') }}" 
                   class="submenu-link {{ isSubActive('admin.invoices.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Daftar Invoice</span>
                </a>
            </li>
            @endcan

            @can('create-invoice-adjustments')
            <li>
                <a href="{{ route('admin.invoice-adjustments.create') }}" 
                   class="submenu-link {{ isSubActive('admin.invoice-adjustments.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Penyesuaian Inv</span>
                </a>
            </li>
            @endcan

            @can('create-bulk-sales-payments')
            <li>
                <a href="{{ route('admin.bulk-sales-payments.index') }}" 
                   class="submenu-link {{ isSubActive('admin.bulk-sales-payments.index') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Terima Bayar</span>
                </a>
            </li>
            @endcan

            @can('review-bulk-sales-payments')
            <li>
                <a href="{{ route('admin.bulk-sales-payments.pending') }}" 
                   class="submenu-link {{ isSubActive('admin.bulk-sales-payments.pending') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Verif Bayar</span>
                </a>
            </li>
            @endcan

            @can('manage-payment-clearance')
            <li>
                <a href="{{ route('admin.payment-clearance.index') }}" 
                   class="submenu-link {{ isSubActive('admin.payment-clearance.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Kliring</span>
                </a>
            </li>
            @endcan

        </ul>
    </li>
    @endif


    {{-- Akuntansi --}}
    @if(Auth::user()->canany(['manage-settings']))

    @php 
        $accRoutes = [
            'admin.expenses.*',
            'admin.fixed-assets.*',
            'admin.equity-transactions.*',
            'admin.loans.*',
            'admin.manual-journals.*',
            'admin.bank-reconciliations.*',
            'admin.closing-book.*',
            'admin.chart-of-accounts.*'
        ]; 
    @endphp

    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($accRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <i class="material-icons text-[20px] {{ iconColor($accRoutes) }}">account_balance</i>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Akuntansi</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($accRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($accRoutes) }} mt-1" data-label="Akuntansi">

            <li>
                <a href="{{ route('admin.expenses.index') }}"
                   class="submenu-link {{ isSubActive('admin.expenses.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Beban Ops</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.fixed-assets.index') }}"
                   class="submenu-link {{ isSubActive('admin.fixed-assets.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Aset Tetap</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.equity-transactions.index') }}"
                   class="submenu-link {{ isSubActive('admin.equity-transactions.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Modal & Prive</span>
                </a>
            </li>

            @can('manage-settings')
            <li>
                <a href="{{ route('admin.manual-journals.index') }}"
                   class="submenu-link {{ isSubActive('admin.manual-journals.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Jurnal Manual</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.bank-reconciliations.index') }}"
                   class="submenu-link {{ isSubActive('admin.bank-reconciliations.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Rekonsiliasi</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.closing-book.index') }}"
                   class="submenu-link {{ isSubActive('admin.closing-book.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Tutup Buku</span>
                </a>
            </li>
            @endcan

            <li>
                <a href="{{ route('admin.chart-of-accounts.index') }}"
                   class="submenu-link {{ isSubActive('admin.chart-of-accounts.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Chart of Accounts</span>
                </a>
            </li>

        </ul>
    </li>

    @endif
    @endif



    {{-- SYSTEM --}}
    <li class="section-header px-3 mt-4 mb-2">
        <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold sidebar-text">System</span>
    </li>

    @if(Auth::user()->canany(['manage-settings', 'manage-users', 'manage-payment-methods']))
    @php 
        $sysRoutes = [
            'admin.settings.*',
            'admin.units.*',
            'admin.taxes.*',
            'admin.payment-methods.*',
            'admin.users.*',
            'admin.roles.*',
            'admin.company-bank-accounts.*'
        ];
    @endphp

    <li class="menu-item group mb-1">
        <button class="sidebar-dropdown-toggle menu-link w-full flex items-center justify-between px-3 py-3 rounded-lg transition-all duration-200 {{ isParentActive($sysRoutes) }}">
            <div class="flex items-center min-w-0 overflow-hidden">
                <i class="material-icons text-[20px] {{ iconColor($sysRoutes) }}">settings</i>
                <span class="sidebar-text ml-3 font-medium text-sm whitespace-nowrap">Pengaturan</span>
            </div>
            <i class="material-icons dropdown-arrow text-[18px] sidebar-text transition-transform duration-200 {{ isArrowRotated($sysRoutes) }}">expand_more</i>
        </button>

        <ul class="submenu {{ isGroupOpen($sysRoutes) }} mt-1" data-label="Pengaturan">

            @can('manage-settings')
            <li>
                <a href="{{ route('admin.settings.index') }}" 
                   class="submenu-link {{ isSubActive('admin.settings.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Profil PT</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.company-bank-accounts.index') }}" 
                   class="submenu-link {{ isSubActive('admin.company-bank-accounts.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Akun Bank</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.units.index') }}" 
                   class="submenu-link {{ isSubActive('admin.units.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Satuan</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.taxes.index') }}" 
                   class="submenu-link {{ isSubActive('admin.taxes.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Pajak</span>
                </a>
            </li>
            @endcan

            @can('manage-payment-methods')
            <li>
                <a href="{{ route('admin.payment-methods.index') }}" 
                   class="submenu-link {{ isSubActive('admin.payment-methods.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Metode Bayar</span>
                </a>
            </li>
            @endcan

            @can('manage-users')
            <li>
                <a href="{{ route('admin.users.index') }}" 
                   class="submenu-link {{ isSubActive('admin.users.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">User</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.roles.index') }}" 
                   class="submenu-link {{ isSubActive('admin.roles.*') }}">
                    <div class="dot"></div>
                    <span class="sidebar-text truncate">Role</span>
                </a>
            </li>
            @endcan

        </ul>
    </li>
    @endif


    {{-- PENGUMUMAN --}}
    @can('manage-announcements')
    <li class="menu-item mb-1">
        <a href="{{ route('admin.announcements.index') }}" 
           class="menu-link flex items-center px-3 py-3 rounded-lg transition-all duration-200 {{ isActive('admin.announcements.*') }}">
            <div class="icon-container w-6 h-6 flex justify-center items-center shrink-0">
                <i class="material-icons text-[20px]">campaign</i>
            </div>
            <span class="sidebar-text ml-3 font-medium text-sm">Pengumuman</span>
        </a>
    </li>
    @endcan


    {{-- VERSION --}}
    <li class="mb-4">
        <div class="menu-link flex items-center px-3 py-3 rounded-lg text-slate-500 cursor-default">
            <div class="icon-container w-6 h-6 flex justify-center items-center shrink-0">
                <i class="material-icons text-[20px]">info</i>
            </div>
            <span class="sidebar-text ml-3 font-medium text-sm">v{{ $systemVersion ?? '1.0.0' }}</span>
        </div>
    </li>

</ul>
