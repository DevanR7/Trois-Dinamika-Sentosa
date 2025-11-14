<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Service helper untuk mengambil ID Akun (COA) default dari database settings.
 * Ini membuat controller tidak perlu tahu 'key' dari setting.
 */
class AccountingSettingService
{
    /**
     * Mengambil ID Akun (COA) untuk Piutang Usaha.
     */
    public function getAccountsReceivableId(): ?int
    {
        // 'acct_default_ar' adalah 'key' yang akan kita simpan di tabel settings
        return (int) Setting::getValue('acct_default_ar');
    }

    /**
     * Mengambil ID Akun (COA) untuk Hutang Dagang.
     */
    public function getAccountsPayableId(): ?int
    {
        // 'acct_default_ap'
        return (int) Setting::getValue('acct_default_ap');
    }

    /**
     * Mengambil ID Akun (COA) untuk Persediaan Barang.
     */
    public function getInventoryId(): ?int
    {
        // 'acct_default_inventory'
        return (int) Setting::getValue('acct_default_inventory');
    }

    /**
     * Mengambil ID Akun (COA) untuk Pendapatan Penjualan.
     */
    public function getSalesRevenueId(): ?int
    {
        // 'acct_default_sales_revenue'
        return (int) Setting::getValue('acct_default_sales_revenue');
    }

    /**
     * Mengambil ID Akun (COA) untuk Harga Pokok Penjualan (HPP).
     */
    public function getCogsId(): ?int
    {
        // 'acct_default_cogs'
        return (int) Setting::getValue('acct_default_cogs');
    }

    /**
     * Mengambil ID Akun (COA) untuk Retur Penjualan / Potongan.
     */
    public function getSalesReturnId(): ?int
    {
        // 'acct_default_sales_return'
        return (int) Setting::getValue('acct_default_sales_return');
    }
    
    /**
     * Mengambil ID Akun (COA) untuk Retur Pembelian / Potongan.
     */
    public function getPurchaseReturnId(): ?int
    {
        // 'acct_default_purchase_return'
        return (int) Setting::getValue('acct_default_purchase_return');
    }

    public function getSupplierDepositId(): ?int
    {
        // 'acct_default_supplier_deposit'
        return (int) Setting::getValue('acct_default_supplier_deposit');
    }

    /**
     * Mengambil ID Akun (COA) untuk Deposit Klien (Kelebihan Bayar).
     * Ini adalah akun Liabilitas (Uang Muka Diterima).
     */
    public function getClientDepositId(): ?int
    {
        // 'acct_default_client_deposit'
        return (int) Setting::getValue('acct_default_client_deposit');
    }

    public function getGatewayAccountId(): ?int
    {
        // 'acct_default_gateway'
        return (int) Setting::getValue('acct_default_gateway');
    }

    public function getRetainedEarningsId(): ?int
{
    return (int) Setting::getValue('acct_default_retained_earnings');
}
}