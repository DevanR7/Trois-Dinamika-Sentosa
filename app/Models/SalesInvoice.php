<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;
// Import model lain yang direlasikan
use App\Models\Client;
use App\Models\InvoiceItem;
use App\Models\Tax;
use App\Models\Payment;
use App\Models\SalesReturn;


class SalesInvoice extends Model
{
    use HasFactory;
    protected $primaryKey = 'invoice_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'user_id_sales',
        'invoice_number',
        'order_date',
        'due_date',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'status',
        'pending_snap_token',      
        'pending_snap_expires_at',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'float',
        'subtotal' => 'float',
        'discount_percentage' => 'float',
        'discount_amount' => 'float',
        'pending_snap_expires_at' => 'datetime',
    ];

    /**
     * Mendapatkan data client yang memiliki invoice ini.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /**
     * Mendapatkan data user (sales) yang membuat invoice ini.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_sales', 'user_id');
    }

    /**
     * Mendapatkan semua item barang dari invoice ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Relasi pivot ke tabel pajak.
     */
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'invoice_tax', 'invoice_id', 'tax_id')
                    ->withPivot('name', 'rate', 'amount');
    }

    /**
     * Relasi ke pembayaran.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Generate nomor invoice baru dengan suffix kondisional.
     *
     * @param int|null $salesUserId ID user sales (jika ada)
     * @param string|null $orderSource Sumber order ('client' atau 'sales')
     * @return string
     */
    public static function generateInvoiceNumber($salesUserId = null, $orderSource = null): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        // Lock baris untuk mencegah duplikasi nomor
        $counter = DB::table('invoice_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('invoice_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            // Tambahkan timestamps saat insert baru
            DB::table('invoice_counters')->insert([
                'ym' => $yearMonth,
                'last_sequence' => $nextSequence,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        $baseNumber = "INV/{$year}/{$month}/{$sequencePadded}";

        // --- LOGIKA SUFFIX BARU ---
        if ($salesUserId) {
            // KASUS 1: Invoice dibuat DENGAN sales ID (dari SO Sales atau manual pilih sales)
            $sales = User::find($salesUserId);
            if ($sales && $sales->sales_code) {
                $baseNumber .= '/' . strtoupper($sales->sales_code);
            }
        } elseif ($orderSource === 'client') {
            // KASUS 2: Invoice dibuat TANPA sales ID, TAPI dari order klien ('client')
            $baseNumber .= '/CO'; // Suffix untuk Client Order
        }
        // KASUS 3: $salesUserId null DAN $orderSource bukan 'client' (misal admin buat manual)
        // Maka tidak ada suffix tambahan.

        return $baseNumber;
    }

    /**
     * Relasi ke SEMUA retur penjualan.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id', 'invoice_id');
    }

    // ========================================================================
    // ✅ PERBAIKAN UNTUK KONDISI 1 DIMULAI DI SINI
    // ========================================================================

    /**
     * BARU: Relasi HANYA ke SalesReturn yang memotong tagihan (deduct_invoice).
     * Ini yang akan kita gunakan untuk menghitung sisa tagihan.
     */
    public function deductingReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id', 'invoice_id')
                    ->where('return_handling_type', 'deduct_invoice');
    }

    /**
     * BARU: Accessor untuk mendapatkan total nilai retur yang HANYA memotong tagihan.
     * Anda bisa memanggil ini di view/controller dengan: $invoice->total_deducting_returns
     *
     * @return float
     */
    public function getTotalDeductingReturnsAttribute(): float
    {
        // 'deductingReturns' adalah nama relasi yang kita buat di atas.
        return $this->deductingReturns()->sum('total_amount');
    }
    
    /**
     * ========================================================================
     * ✅ FUNGSI DIPERBARUI: getRemainingBalanceAttribute
     * ========================================================================
     *
     * Accessor untuk mendapatkan sisa tagihan.
     * Logikanya sekarang sederhana: Total Tagihan - Total Terbayar.
     *
     * @return float
     */
    public function getRemainingBalanceAttribute(): float
    {
        // $this->total_due adalah accessor baru yang kita buat di atas.
        // $this->amount_paid adalah kolom DB yang disinkronkan oleh updatePaymentStatus.
        $balance = $this->total_due - $this->amount_paid;
        
        // Kita tidak lagi mengizinkan sisa saldo negatif di sini,
        // karena kelebihan bayar ditangani oleh ClientLedger.
        // return $balance; // <-- Versi lama Anda mengizinkan negatif
        
        // Versi yang lebih aman:
        return max(0, $balance);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'sales_invoice_id', 'invoice_id');
    }

    public function updatePaymentStatus()
    {
        if ($this->status == 'cancelled') {
            return;
        }

        // 1. Ambil total pembayaran yang sudah selesai (sumber data: pembayaran)
        $totalPaid = $this->payments()->where('status', 'completed')->sum('amount');
        
        // 2. Ambil total tagihan sebenarnya (sumber data: accessor baru kita)
        // Kita panggil relasi agar accessor 'total_due' bisa bekerja efisien
        $this->loadMissing(['adjustments', 'deductingReturns']);
        $totalDue = $this->total_due; // <-- Menggunakan accessor 'getTotalDueAttribute'

        // Bulatkan untuk menghindari masalah floating point
        $totalDue = round($totalDue, 2);
        $totalPaid = round($totalPaid, 2);
        
        // 3. Tentukan status baru
        $newStatus = 'unpaid'; // Default
        
        if ($totalPaid >= ($totalDue - 0.01)) { // Toleransi 1 sen
            $newStatus = 'paid';
        } elseif ($totalPaid > 0.01) {
            $newStatus = 'partially_paid';
        }
        
        // 4. Update HANYA jika status berubah atau amount_paid tidak sinkron
        if ($this->status != $newStatus || $this->amount_paid != $totalPaid) {
            $this->update([
                'status' => $newStatus,
                'amount_paid' => $totalPaid // Sinkronkan kolom amount_paid
            ]);
        }
        
        // 5. Jika Lunas, hapus token pending (jika ada)
        if ($newStatus == 'paid') {
            if ($this->pending_snap_token) {
                $this->update([
                    'pending_snap_token' => null,
                    'pending_snap_expires_at' => null
                ]);
            }
            // Lepaskan kredit pending
            ClientLedger::where('sales_invoice_id', $this->invoice_id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'available',
                            'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                        ]);
        }
    }

    /**
     * ========================================================================
     * ✅ FUNGSI BARU: PUSAT LOGIKA TOTAL TAGIHAN
     * ========================================================================
     *
     * Accessor untuk mendapatkan total tagihan SEBENARNYA (setelah
     * penyesuaian dan retur). Ini adalah satu-satunya sumber kebenaran.
     *
     * @return float
     */
    public function getTotalDueAttribute(): float
    {
        // 1. Mulai dengan total asli
        $total = $this->total_amount;

        // 2. Tambah/kurangi penyesuaian (Nota Debit/Kredit)
        // Kita gunakan relasi yang sudah di-load jika ada, atau query baru jika tidak.
        
        // ✅ PERBAIKAN: $this.relationLoaded() diubah menjadi $this->relationLoaded()
        if ($this->relationLoaded('adjustments')) {
            $totalAdjustments = $this->adjustments->sum(function($adj) {
                return $adj->type == 'debit_note' ? $adj->amount : -$adj->amount;
            });
        } else {
            $totalDebitNotes = $this->adjustments()->where('type', 'debit_note')->sum('amount');
            $totalCreditNotes = $this->adjustments()->where('type', 'credit_note')->sum('amount');
            $totalAdjustments = $totalDebitNotes - $totalCreditNotes;
        }
        $total += $totalAdjustments;

        // 3. Kurangi retur yang memotong tagihan
        // Kita gunakan relasi yang sudah di-load jika ada, atau accessor jika tidak.

        // ✅ PERBAIKAN: $this.relationLoaded() diubah menjadi $this->relationLoaded()
        if ($this->relationLoaded('deductingReturns')) {
            $total -= $this->deductingReturns->sum('total_amount');
        } else {
            // Gunakan accessor 'total_deducting_returns' yang memanggil query baru
            $total -= $this->total_deducting_returns;
        }

        return (float) $total;
    }
}