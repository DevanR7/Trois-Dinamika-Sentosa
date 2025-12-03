<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\PurchaseReturnItem;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'supplier_id',
        'purchase_order_id',
        'user_id',
        'return_date',
        'return_handling_type',
        'notes',
        'total_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

     public static function generateReturnNumber(): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        $counter = DB::table('purchase_return_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('purchase_return_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            DB::table('purchase_return_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence]);
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "PR/{$year}/{$month}/{$sequencePadded}";
    }

    // Relasi ke tabel PurchaseOrder (induk)
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }

    // Relasi ke tabel Supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Relasi ke tabel User yang memproses
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke item-item retur (anak)
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_id', 'return_id');
    }
}