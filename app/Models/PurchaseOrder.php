<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseOrderItem;
use App\Models\Tax;
use App\Models\PurchaseOrderPayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseOrderAdjustment;
use App\Models\SupplierLedger;
use App\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;
    
    protected $primaryKey = 'po_id';

    protected $fillable = [
        'po_number',
        'supplier_invoice_number',
        'supplier_id',
        'requester_user_id',
        'user_id_admin',
        'order_date',
        'due_date',
        'expected_delivery_date' => 'date',
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
     * Generator Nomor PO Format: PO/SUP-{ID}/YYYY/MM/XXXX
     */
    public static function generatePoNumber($supplierId): string
{
    if (empty($supplierId)) {
        throw new \Exception("Supplier ID diperlukan.");
    }

    $ym = now()->format('Ym');

    return DB::transaction(function() use ($ym, $supplierId) {
        // 1. Lock baris counter untuk supplier & bulan ini
        // Jika belum ada, kita buat dulu (atomic lock)
        $counter = DB::table('po_counters')
            ->where('ym', $ym)
            ->where('supplier_id', $supplierId)
            ->lockForUpdate() // <--- INI KUNCINYA (Mencegah user lain baca baris ini)
            ->first();

        if (!$counter) {
            // Jika belum ada, buat baru mulai dari 1
            // Gunakan insertOrIgnore atau try-catch untuk safety ganda
            try {
                DB::table('po_counters')->insert([
                    'ym' => $ym,
                    'supplier_id' => $supplierId,
                    'last_sequence' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $nextSeq = 1;
            } catch (\Exception $e) {
                // Jika insert gagal (race condition sangat langka), coba baca lagi
                $counter = DB::table('po_counters')
                    ->where('ym', $ym)
                    ->where('supplier_id', $supplierId)
                    ->lockForUpdate()
                    ->first();
                $nextSeq = $counter->last_sequence + 1;
                DB::table('po_counters')
                    ->where('ym', $ym)
                    ->where('supplier_id', $supplierId)
                    ->update(['last_sequence' => $nextSeq, 'updated_at' => now()]);
            }
        } else {
            // Jika sudah ada, increment
            $nextSeq = $counter->last_sequence + 1;
            DB::table('po_counters')
                ->where('ym', $ym)
                ->where('supplier_id', $supplierId)
                ->update(['last_sequence' => $nextSeq, 'updated_at' => now()]);
        }

        $seqFormatted = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        return "PO/SUP-{$supplierId}/" . now()->format('Y') . "/" . now()->format('m') . "/" . $seqFormatted;
    });
}

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

    public function getRemainingBalanceAttribute(): float
    {
        $balance = $this->grand_total;
        $balance -= $this->amount_paid;
        $balance -= $this->total_returned; 
        return max(0, $balance);
    }

    public function updatePaymentStatus()
    {
        if ($this->status == 'cancelled') {
            return;
        }

        $totalPaid = $this->payments()->sum('amount');
        $this->loadMissing(['adjustments', 'deductingReturns']); 
        
        $totalAdjustments = $this->adjustments->sum(function($adj) {
            return $adj->type == 'debit_note' ? $adj->amount : -$adj->amount;
        });
        
        $totalDeductingReturns = $this->deductingReturns->sum('total_amount');
        
        $totalDue = $this->total_amount + $totalAdjustments - $totalDeductingReturns;
        $totalDue = round($totalDue, 2);
        $totalPaid = round($totalPaid, 2);
        
        $newStatus = 'unpaid';
        
        // Toleransi perbedaan koma kecil
        if ($totalPaid >= ($totalDue - 0.01)) { 
            $newStatus = 'paid';
        } elseif ($totalPaid > 0.01) {
            $newStatus = 'partially_paid';
        }

        // 1. Update Status PO
        if ($this->payment_status != $newStatus || $this->amount_paid != $totalPaid) {
            $this->update([
                'payment_status' => $newStatus,
                'amount_paid' => $totalPaid
            ]);
        }

        // 2. Logika Sinkronisasi Ledger (FIX UTAMA DISINI)
        if ($newStatus == 'paid') {
            // A. JIKA LUNAS: Lepaskan Deposit yang ditahan (Pending -> Available)
            try {
                SupplierLedger::where('purchase_order_id', $this->po_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')") // Hapus label (Ditahan)
                            ]);
            } catch (\Exception $e) {
                Log::error("Gagal melepaskan SupplierLedger: " . $e->getMessage());
            }
        } else {
            // B. JIKA BELUM/TIDAK LUNAS (Kasus Rollback): Tahan Kembali Deposit (Available -> Pending)
            // Hanya kunci kembali jika ledger tersebut belum dipakai (masih utuh amount-nya di sistem logika Anda)
            // Atau secara default kita kunci semua yang terkait PO ini.
            try {
                // Kita cari ledger "Available" yang berasal dari Retur PO ini
                SupplierLedger::where('purchase_order_id', $this->po_id)
                            ->where('status', 'available')
                            ->where('reference_type', PurchaseReturn::class) // Hanya kunci ledger yang berasal dari Retur
                            ->update([
                                'status' => 'pending',
                                'description' => DB::raw("CONCAT(description, ' (Ditahan)')") // Tambahkan label (Ditahan)
                            ]);
            } catch (\Exception $e) {
                Log::error("Gagal mengunci kembali SupplierLedger: " . $e->getMessage());
            }
        }
    }
}