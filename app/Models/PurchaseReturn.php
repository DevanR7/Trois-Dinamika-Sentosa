<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\PurchaseReturn
 *
 * @property int $return_id
 * @property string $return_number
 * @property int $supplier_id
 * @property int $purchase_order_id
 * @property int $user_id User yang memproses retur
 * @property \Illuminate\Support\Carbon $return_date
 * @property string $return_handling_type Aksi: potong tagihan atau simpan jadi deposit
 * @property string|null $notes
 * @property string $total_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseReturnItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\PurchaseOrder $purchaseOrder
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereReturnHandlingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereReturnNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturn whereUserId($value)
 * @mixin \Eloquent
 */
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