<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        // 1000 - ASET
        $aset = ChartOfAccount::create(['account_number' => '1000', 'account_name' => 'ASET', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            $asetLancar = ChartOfAccount::create(['parent_account_id' => $aset->account_id, 'account_number' => '1100', 'account_name' => 'Aset Lancar', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                $kasBank = ChartOfAccount::create(['parent_account_id' => $asetLancar->account_id, 'account_number' => '1101', 'account_name' => 'Kas & Bank', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $kasBank->account_id, 'account_number' => '1101.01', 'account_name' => 'Kas Tunai', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $kasBank->account_id, 'account_number' => '1101.02', 'account_name' => 'Bank BCA', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $kasBank->account_id, 'account_number' => '1101.03', 'account_name' => 'Bank Mandiri', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $kasBank->account_id, 'account_number' => '1101.99', 'account_name' => 'Kas Midtrans (Gateway)', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);

                ChartOfAccount::create(['parent_account_id' => $asetLancar->account_id, 'account_number' => '1102', 'account_name' => 'Piutang Usaha', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $asetLancar->account_id, 'account_number' => '1105', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $asetLancar->account_id, 'account_number' => '1106', 'account_name' => 'Uang Muka Pembelian (Deposit Supplier)', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);

            $asetTetap = ChartOfAccount::create(['parent_account_id' => $aset->account_id, 'account_number' => '1500', 'account_name' => 'Aset Tetap', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $asetTetap->account_id, 'account_number' => '1501', 'account_name' => 'Kendaraan', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $asetTetap->account_id, 'account_number' => '1502', 'account_name' => 'Peralatan Kantor', 'account_type' => 'Aset', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
                
                $akumulasi = ChartOfAccount::create(['parent_account_id' => $asetTetap->account_id, 'account_number' => '1590', 'account_name' => 'Akumulasi Penyusutan', 'account_type' => 'Aset', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $akumulasi->account_id, 'account_number' => '1591', 'account_name' => 'Akumulasi Penyusutan - Kendaraan', 'account_type' => 'Aset', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
                    ChartOfAccount::create(['parent_account_id' => $akumulasi->account_id, 'account_number' => '1592', 'account_name' => 'Akumulasi Penyusutan - Peralatan', 'account_type' => 'Aset', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);

        // 2000 - LIABILITAS
        $liabilitas = ChartOfAccount::create(['account_number' => '2000', 'account_name' => 'LIABILITAS', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
            $liabilitasLancar = ChartOfAccount::create(['parent_account_id' => $liabilitas->account_id, 'account_number' => '2100', 'account_name' => 'Liabilitas Jangka Pendek', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $liabilitasLancar->account_id, 'account_number' => '2101', 'account_name' => 'Hutang Dagang', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $liabilitasLancar->account_id, 'account_number' => '2105', 'account_name' => 'Uang Muka Diterima (Deposit Klien)', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);

            $liabilitasPanjang = ChartOfAccount::create(['parent_account_id' => $liabilitas->account_id, 'account_number' => '2200', 'account_name' => 'Liabilitas Jangka Panjang', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
                ChartOfAccount::create(['parent_account_id' => $liabilitasPanjang->account_id, 'account_number' => '2201', 'account_name' => 'Hutang Bank (Pinjaman)', 'account_type' => 'Liabilitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);

        // 3000 - EKUITAS
        $ekuitas = ChartOfAccount::create(['account_number' => '3000', 'account_name' => 'EKUITAS', 'account_type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $ekuitas->account_id, 'account_number' => '3101', 'account_name' => 'Modal Setor', 'account_type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $ekuitas->account_id, 'account_number' => '3102', 'account_name' => 'Prive (Penarikan Modal)', 'account_type' => 'Ekuitas', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $ekuitas->account_id, 'account_number' => '3103', 'account_name' => 'Laba Ditahan', 'account_type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);

        // 4000 - PENDAPATAN
        $pendapatan = ChartOfAccount::create(['account_number' => '4000', 'account_name' => 'PENDAPATAN', 'account_type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $pendapatan->account_id, 'account_number' => '4101', 'account_name' => 'Pendapatan Penjualan', 'account_type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $pendapatan->account_id, 'account_number' => '4102', 'account_name' => 'Retur & Potongan Penjualan', 'account_type' => 'Pendapatan', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);

        // 5000 - HPP
        $hpp = ChartOfAccount::create(['account_number' => '5000', 'account_name' => 'HARGA POKOK PENJUALAN', 'account_type' => 'HPP', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $hpp->account_id, 'account_number' => '5101', 'account_name' => 'Harga Pokok Penjualan', 'account_type' => 'HPP', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $hpp->account_id, 'account_number' => '5102', 'account_name' => 'Potongan Pembelian', 'account_type' => 'HPP', 'normal_balance' => 'Kredit', 'created_at' => $now, 'updated_at' => $now]);

        // 6000 - BEBAN
        $beban = ChartOfAccount::create(['account_number' => '6000', 'account_name' => 'BEBAN OPERASIONAL', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6101', 'account_name' => 'Beban Gaji', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6102', 'account_name' => 'Beban Listrik, Air, & Internet', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6103', 'account_name' => 'Beban Bunga', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6104', 'account_name' => 'Beban Sewa', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6105', 'account_name' => 'Beban Pemasaran', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6109', 'account_name' => 'Beban Penyusutan - Kendaraan', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6110', 'account_name' => 'Beban Penyusutan - Peralatan', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
            ChartOfAccount::create(['parent_account_id' => $beban->account_id, 'account_number' => '6201', 'account_name' => 'Beban Selisih Stok', 'account_type' => 'Beban', 'normal_balance' => 'Debit', 'created_at' => $now, 'updated_at' => $now]);
    }
}