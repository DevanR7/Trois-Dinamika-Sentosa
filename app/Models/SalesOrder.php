<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SalesOrder extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'order_id';

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'float',
    ];

     public static function generateOrderNumber($salesUserId = null): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        $counter = DB::table('sales_order_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('sales_order_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            DB::table('sales_order_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence]);
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        $baseNumber = "SO/{$year}/{$month}/{$sequencePadded}";

        if ($salesUserId) {
        $sales = User::find($salesUserId);
        if ($sales && $sales->sales_code) {
            $baseNumber .= '/' . strtoupper($sales->sales_code);
        }
    }
    return $baseNumber;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_sales', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id', 'order_id');
    }
}