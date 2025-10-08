<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB; // <-- Pastikan baris ini ada

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
}