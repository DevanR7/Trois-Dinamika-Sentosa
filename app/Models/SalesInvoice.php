<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SalesInvoice extends Model
{
    use HasFactory;
    protected $primaryKey = 'invoice_id';

/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'user_id_sales',
        'invoice_number',
        'order_date',
        'due_date',
        'subtotal',
        'discount_percentage', 
        'discount_amount',
        'total_amount',
        'amount_paid',
        'status',
        'notes'
    ];

   /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date', 
        'total_amount' => 'float',
        'subtotal' => 'float',
        'discount_percentage' => 'float',
        'discount_amount' => 'float',
    ];

    /**
     * Mendapatkan data client yang memiliki invoice ini.
     * Ini adalah relasi yang menyebabkan error.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /**
     * Mendapatkan data user (sales) yang membuat invoice ini.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_sales', 'user_id');
    }

    /**
     * Mendapatkan semua item barang dari invoice ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'invoice_id');
    }

    public function taxes()
{
    return $this->belongsToMany(Tax::class, 'invoice_tax', 'invoice_id', 'tax_id')
                ->withPivot('name', 'rate', 'amount');
}

public function payments(): HasMany
{
    return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
}

public static function generateInvoiceNumber($salesUserId = null): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');

        // Lock baris untuk mencegah duplikasi nomor saat ada >1 user membuat invoice bersamaan
        $counter = DB::table('invoice_counters')->where('ym', $yearMonth)->lockForUpdate()->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('invoice_counters')->where('ym', $yearMonth)->update(['last_sequence' => $nextSequence]);
        } else {
            $nextSequence = 1;
            DB::table('invoice_counters')->insert(['ym' => $yearMonth, 'last_sequence' => $nextSequence]);
        }

        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        $baseNumber = "INV/{$year}/{$month}/{$sequencePadded}";

    // Logika baru untuk menambahkan kode sales
    if ($salesUserId) {
        $sales = User::find($salesUserId);
        if ($sales && $sales->sales_code) {
            $baseNumber .= '/' . strtoupper($sales->sales_code);
        }
    }
    return $baseNumber;
    }

    public function returns(): HasMany
{
    return $this->hasMany(SalesReturn::class, 'sales_invoice_id', 'invoice_id');
}
}