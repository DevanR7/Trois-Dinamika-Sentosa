<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Models\Client;
use App\Models\User;
use App\Models\SalesReturnItem;
use App\Traits\LogsActivity;

class SalesReturn extends Model
{
    use HasFactory, LogsActivity;
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

    return DB::transaction(function() use ($yearMonth, $year, $month) {
        $counter = DB::table('sales_return_counters')
            ->where('ym', $yearMonth)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('sales_return_counters')
                ->where('ym', $yearMonth)
                ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
        } else {
            try {
                $nextSequence = 1;
                DB::table('sales_return_counters')->insert([
                    'ym' => $yearMonth, 
                    'last_sequence' => $nextSequence,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } catch (\Exception $e) {
                $counter = DB::table('sales_return_counters')
                    ->where('ym', $yearMonth)
                    ->lockForUpdate()
                    ->first();
                $nextSequence = $counter->last_sequence + 1;
                DB::table('sales_return_counters')
                    ->where('ym', $yearMonth)
                    ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
            }
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        return "SR/{$year}/{$month}/{$sequencePadded}";
    });
}

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'return_id', 'return_id');
    }
}