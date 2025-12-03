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
use App\Models\SalesInvoice; // Tambahkan ini jika relasi invoice_id digunakan

class Order extends Model
{
    use HasFactory;

    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     * Anda HARUS mendefinisikan field mana saja yang boleh diisi
     * saat menggunakan $client->orders()->create(...) atau Order::create(...).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',        // Wajib ada jika create dari Client
        'order_number',     // Wajib ditambahkan karena error sebelumnya
        'user_id_sales',    // Boleh null
        'invoice_id',       // Boleh null
        'order_date',
        'total_amount',
        'status',
        'order_source',
        'notes',            // Boleh null
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'float',
    ];

    /**
     * Generate a unique order number.
     *
     * @param int|null $salesUserId
     * @return string
     */
     public static function generateOrderNumber($salesUserId = null): string
     {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        // Gunakan transaksi database untuk mencegah race condition
        $counter = DB::table('sales_order_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('sales_order_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            DB::table('sales_order_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence, 'created_at' => now(), 'updated_at' => now()]); // Tambah timestamps
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        // Pertimbangkan prefix berbeda untuk order klien vs sales? Misal "CO" vs "SO"
        $prefix = $salesUserId ? "SO" : "CO"; // Contoh: CO untuk Client Order
        $baseNumber = "{$prefix}/{$year}/{$month}/{$sequencePadded}";

        if ($salesUserId) {
            $sales = User::find($salesUserId);
            if ($sales && $sales->sales_code) {
                $baseNumber .= '/' . strtoupper($sales->sales_code);
            }
        }
        return $baseNumber;
     }

    // --- RELASI ---

    /**
     * Get the client that owns the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /**
     * Get the sales user associated with the order (if any).
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_sales', 'user_id');
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    /**
     * Get the change requests for the order.
     */
    public function changeRequests(): HasMany
    {
        // Mengurutkan berdasarkan yang terbaru
        return $this->hasMany(OrderChangeRequest::class, 'order_id', 'order_id')->latest('request_id'); // Order by ID
    }

    /**
     * Get the invoice associated with the order (if any).
     */
    public function invoice(): BelongsTo
    {
         return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }
}