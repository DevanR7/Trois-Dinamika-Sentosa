<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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