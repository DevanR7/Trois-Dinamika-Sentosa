<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrderExport implements FromView, WithColumnWidths, WithEvents
{
    protected $purchaseOrder;
    protected $itemCount;

    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->itemCount = $purchaseOrder->items->count() > 0 ? $purchaseOrder->items->count() : 1;
    }

    public function view(): View
    {
        return view('purchase_orders.excel_template', [
            'purchaseOrder' => $this->purchaseOrder
        ]);
    }

    /**
     * Mengatur lebar setiap kolom secara spesifik agar sesuai faktur.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  // Kolom checkbox/tanda ✓
            'B' => 8,  // Qty
            'C' => 35, // Nama Barang
            'D' => 15, // Harga
            'E' => 8,  // Disc%
            'F' => 15, // Jumlah
        ];
    }

    /**
     * Mendaftarkan event AfterSheet untuk melakukan styling setelah sheet terisi.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- Pengaturan Umum ---
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
                
                // --- Merge Cell & Alignment ---
                
                // Header Perusahaan
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Alamat Perusahaan
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // NPWP
                $sheet->mergeCells('A3:F3');
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Judul "Kepada Yth:"
                $sheet->mergeCells('A4:C4');
                $sheet->getStyle('A4')->getFont()->setBold(true);

                // Info Customer
                $sheet->mergeCells('A5:C5'); // Nama
                $sheet->mergeCells('A6:C6'); // Alamat
                $sheet->mergeCells('A7:C7'); // Email

                // Tanggal & No Faktur
                $sheet->mergeCells('D4:F4'); // Tanggal
                $sheet->mergeCells('D5:F5'); // No Faktur
                $sheet->mergeCells('D6:F6'); // Jatuh Tempo

                // Header Tabel
                $headerRow = 8;
                $sheet->mergeCells('A'.$headerRow.':A'.$headerRow); // Kolom checkbox
                $sheet->mergeCells('B'.$headerRow.':B'.$headerRow); // Qty
                $sheet->mergeCells('C'.$headerRow.':C'.$headerRow); // Nama Barang
                $sheet->mergeCells('D'.$headerRow.':D'.$headerRow); // Harga
                $sheet->mergeCells('E'.$headerRow.':E'.$headerRow); // Disc%
                $sheet->mergeCells('F'.$headerRow.':F'.$headerRow); // Jumlah

                // Styling Header Tabel
                $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6E6');

                // Menghitung posisi baris untuk bagian bawah (footer) secara dinamis
                $startDataRow = $headerRow + 1;
                $endDataRow = $startDataRow + $this->itemCount - 1;
                
                // Area ringkasan pembayaran
                $summaryStartRow = $endDataRow + 2;
                
                // Merge untuk bagian subtotal, diskon, dll
                $sheet->mergeCells('A'.$summaryStartRow.':E'.$summaryStartRow); // Subtotal
                $sheet->mergeCells('A'.($summaryStartRow+1).':E'.($summaryStartRow+1)); // Disc/Fee
                $sheet->mergeCells('A'.($summaryStartRow+2).':E'.($summaryStartRow+2)); // Disc Pembulatan
                $sheet->mergeCells('A'.($summaryStartRow+4).':E'.($summaryStartRow+4)); // DPP
                $sheet->mergeCells('A'.($summaryStartRow+5).':E'.($summaryStartRow+5)); // PPN
                $sheet->mergeCells('A'.($summaryStartRow+6).':E'.($summaryStartRow+6)); // Ongkos Kirim
                $sheet->mergeCells('A'.($summaryStartRow+8).':E'.($summaryStartRow+8)); // Total

                // Footer/catatan
                $footerStartRow = $summaryStartRow + 10;
                $sheet->mergeCells('A'.$footerStartRow.':F'.$footerStartRow); // Catatan 1
                $sheet->mergeCells('A'.($footerStartRow+1).':F'.($footerStartRow+1)); // Catatan 2
                $sheet->mergeCells('A'.($footerStartRow+2).':F'.($footerStartRow+2)); // Catatan 3

                // Tanda tangan
                $signatureRow = $footerStartRow + 4;
                $sheet->mergeCells('A'.$signatureRow.':C'.($signatureRow+2)); // Penerima
                $sheet->mergeCells('D'.$signatureRow.':F'.($signatureRow+2)); // Hormat Kami

                // --- Pengaturan Border ---
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ]
                    ]
                ];

                // Border untuk tabel utama
                $tableRange = 'A'.$headerRow.':F'.$endDataRow;
                $sheet->getStyle($tableRange)->applyFromArray($styleArray);

                // Border untuk ringkasan pembayaran
                $summaryRange = 'A'.$summaryStartRow.':F'.($summaryStartRow+8);
                $sheet->getStyle($summaryRange)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                // Alignment untuk kolom angka
                $sheet->getStyle('D'.($headerRow+1).':F'.$endDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F'.$summaryStartRow.':F'.($summaryStartRow+8))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Format angka
                $numberRange = 'D'.($headerRow+1).':F'.($summaryStartRow+8);
                $sheet->getStyle($numberRange)->getNumberFormat()->setFormatCode('#,##0');

                // Bold untuk total
                $sheet->getStyle('A'.($summaryStartRow+8).':F'.($summaryStartRow+8))->getFont()->setBold(true);

                // Auto size untuk beberapa kolom
                $sheet->getColumnDimension('C')->setAutoSize(true);
            },
        ];
    }
}