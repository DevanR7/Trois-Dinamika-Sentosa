<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App\Models\PurchaseOrder
 *
 * @property int $po_id
 * @property string $po_number
 * @property string|null $supplier_invoice_number
 * @property int $supplier_id
 * @property int|null $requester_user_id
 * @property int $user_id_admin
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string|null $expected_delivery_date
 * @property float $total_amount
 * @property float|null $subtotal
 * @property int|null $tax_id
 * @property bool $apply_disc_fee
 * @property float|null $disc_fee_percent
 * @property float|null $disc_fee_amount
 * @property bool $apply_rounding_discount
 * @property float|null $rounding_discount_amount
 * @property bool $use_custom_dpp_factor
 * @property float|null $custom_dpp_factor
 * @property float $shipping_amount
 * @property float|null $taxable_amount
 * @property float|null $dpp
 * @property float|null $ppn
 * @property float|null $grand_total
 * @property string $amount_paid
 * @property string $total_returned
 * @property string $status
 * @property string $payment_status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderAdjustment> $adjustments
 * @property-read int|null $adjustments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseReturn> $deductingReturns
 * @property-read int|null $deducting_returns_count
 * @property-read float $remaining_balance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User|null $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseReturn> $returns
 * @property-read int|null $returns_count
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Tax|null $tax
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereApplyDiscFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereApplyRoundingDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereCustomDppFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereDiscFeeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereDiscFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereDpp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereExpectedDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder wherePoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder wherePoNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder wherePpn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereRequesterUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereRoundingDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereShippingAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereSupplierInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereTaxableAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereTotalReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereUseCustomDppFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrder whereUserIdAdmin($value)
 * @mixin \Eloquent
 */
class PurchaseOrder extends Model
{
    use HasFactory;
    protected $primaryKey = 'po_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_number',
        'supplier_invoice_number',
        'supplier_id',
        'requester_user_id',
        'user_id_admin',
        'order_date',
        'due_date',
        'expected_delivery_date',
        'status',
        'notes',
        'tax_id',
        'subtotal',
        'apply_disc_fee',
        'disc_fee_percent',
        'disc_fee_amount',
        'apply_rounding_discount',
        'rounding_discount_amount',
        'use_custom_dpp_factor',
        'custom_dpp_factor',
        'shipping_amount',
        'taxable_amount',
        'dpp',
        'ppn',
        'payment_status',
        'total_amount',
        'grand_total',
        'amount_paid',
        'total_returned', 
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'apply_disc_fee' => 'boolean',
        'apply_rounding_discount' => 'boolean',
        'use_custom_dpp_factor' => 'boolean',
        'subtotal' => 'float',
        'disc_fee_percent' => 'float',
        'disc_fee_amount' => 'float',
        'rounding_discount_amount' => 'float',
        'custom_dpp_factor' => 'float',
        'shipping_amount' => 'float',
        'taxable_amount' => 'float',
        'dpp' => 'float',
        'ppn' => 'float',
        'total_amount' => 'float',
        'grand_total' => 'float',
        
    ];
    
    /**
     * Generate a unique Purchase Order number based on a monthly counter.
     *
     * @return string
     */
    public static function generatePoNumber(): string
    {
        $ym = now()->format('Ym');
        $counterRow = DB::table('po_counters')->where('ym', $ym)->lockForUpdate()->first();

        if ($counterRow) {
            $nextSeq = $counterRow->last_sequence + 1;
            DB::table('po_counters')->where('ym', $ym)->update(['last_sequence' => $nextSeq, 'updated_at' => now()]);
        } else {
            $nextSeq = 1;
            DB::table('po_counters')->insert(['ym' => $ym, 'last_sequence' => $nextSeq, 'created_at' => now(), 'updated_at' => now()]);
        }

        $seqFormatted = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        return "PO/" . now()->format('Y') . "/" . now()->format('m') . "/" . $seqFormatted;
    }
    
    // =================================================================
    // RELASI ANTAR TABEL
    // =================================================================
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id', 'user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id', 'po_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function payments(): HasMany
    {
    return $this->hasMany(PurchaseOrderPayment::class, 'po_id', 'po_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_order_id', 'po_id');
    }

    public function deductingReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_order_id', 'po_id')
                    ->where('return_handling_type', 'deduct_invoice');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PurchaseOrderAdjustment::class, 'purchase_order_id', 'po_id');
    }

    /**
     * Accessor untuk mendapatkan sisa utang yang belum dibayar.
     * Ini adalah SATU-SATUNYA sumber kebenaran untuk sisa utang.
     *
     * @return float
     */
    public function getRemainingBalanceAttribute(): float
    {
        // 1. Mulai dengan total tagihan asli
        $balance = $this->total_amount;

        // 2. Tambahkan semua Nota Debit (Kita ditagih lebih)
        // Kita gunakan query langsung agar mendapatkan data terbaru
        $totalDebitNotes = $this->adjustments()->where('type', 'debit_note')->sum('amount');
        $balance += $totalDebitNotes;

        // 3. Kurangi semua pembayaran (gunakan kolom 'amount_paid' yg sudah disinkronisasi)
        $balance -= $this->amount_paid;
        
        // 4. Kurangi semua retur yang "Potong Nota"
        // Kita gunakan $this->total_returned (kolom yg sudah disinkronisasi)
        $balance -= $this->total_returned; 

        // 5. Kurangi semua Nota Kredit (Kita dapat diskon/potongan)
        $totalCreditNotes = $this->adjustments()->where('type', 'credit_note')->sum('amount');
        $balance -= $totalCreditNotes;
        
        // ==========================================================
        // PERBAIKAN: Hapus 'max(0, $balance)'
        // Kita HARUS mengizinkan nilai negatif agar controller bisa
        // mendeteksi kelebihan bayar.
        // ==========================================================
        return $balance;
    }

    /**
     * Fungsi ini sudah BENAR.
     * Fungsi ini menghitung tagihan akhir dan total pembayaran
     * lalu menyimpannya ke database.
     */
    public function updatePaymentStatus()
    {
        // Jangan update jika sudah dibatalkan
        if ($this->status == 'cancelled') {
            return;
        }

        // 1. Ambil total pembayaran
        $totalPaid = $this->payments()->sum('amount');

        // 2. Hitung total tagihan yang sebenarnya (setelah koreksi/retur)
        $this->loadMissing(['adjustments', 'deductingReturns']); 
        
        $totalAdjustments = $this->adjustments->sum(function($adj) {
            return $adj->type == 'debit_note' ? $adj->amount : -$adj->amount;
        });
        
        $totalDeductingReturns = $this->deductingReturns->sum('total_amount');
        
        // Ini adalah total tagihan akhir
        $totalDue = $this->total_amount + $totalAdjustments - $totalDeductingReturns;
        
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
        if ($this->payment_status != $newStatus || $this->amount_paid != $totalPaid) {
            $this->update([
                'payment_status' => $newStatus,
                'amount_paid' => $totalPaid
            ]);
        }
        
        // 5. Jika Lunas, lepaskan deposit pending
        if ($newStatus == 'paid') {
            // (Kita asumsikan SupplierLedger ada, berdasarkan controller Anda)
            try {
                SupplierLedger::where('purchase_order_id', $this->po_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                            ]);
            } catch (\Exception $e) {
                // Tangani jika model SupplierLedger tidak ada
                Log::error("Gagal melepaskan SupplierLedger: " . $e->getMessage());
            }
        }
    }
}