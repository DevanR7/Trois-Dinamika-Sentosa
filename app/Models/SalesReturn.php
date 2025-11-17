<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\SalesReturn
 *
 * @property int $return_id
 * @property string $return_number
 * @property int $client_id
 * @property int $sales_invoice_id
 * @property int $user_id User yang memproses retur
 * @property \Illuminate\Support\Carbon $return_date
 * @property string $return_handling_type Aksi: potong tagihan atau simpan jadi kredit
 * @property string|null $notes
 * @property float $total_amount
 * @property float $total_hpp_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client $client
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesReturnItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\SalesInvoice $salesInvoice
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn query()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereReturnHandlingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereReturnNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereSalesInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereTotalHppAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturn whereUserId($value)
 * @mixin \Eloquent
 */
class SalesReturn extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'client_id',
        'sales_invoice_id',
        'user_id',
        'return_date',
        'return_handling_type',
        'notes',
        'total_amount',
        'total_hpp_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'float',
        'total_hpp_amount' => 'float',
    ];

     public static function generateReturnNumber(): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        $counter = DB::table('sales_return_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('sales_return_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            DB::table('sales_return_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence]);
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "SR/{$year}/{$month}/{$sequencePadded}";
    }

    // Relasi ke tabel SalesInvoice (induk)
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }

    // Relasi ke tabel Client
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
    
    // Relasi ke tabel User yang memproses
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke item-item retur (anak)
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'return_id', 'return_id');
    }
}