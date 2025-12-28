<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use App\Models\OrderItem;
use App\Models\OrderChangeRequest;
use App\Models\SalesInvoice;

class Order extends Model
{
    use HasFactory;
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'client_id',        
        'order_number',     
        'user_id_sales',    
        'invoice_id',       
        'order_date',
        'total_amount',
        'status',
        'order_source',
        'notes',          
    ];

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
            DB::table('sales_order_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence, 'created_at' => now(), 'updated_at' => now()]);
        }
        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        $prefix = $salesUserId ? "SO" : "CO"; 
        $baseNumber = "{$prefix}/{$year}/{$month}/{$sequencePadded}";

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
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(OrderChangeRequest::class, 'order_id', 'order_id')->latest('request_id');
    }

    public function invoice(): BelongsTo
    {
         return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }
}