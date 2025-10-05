<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'invoice_date',
        'due_date',
        'total_amount',
        'amount_paid',
        'status',
    ];

   /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'total_amount' => 'float',
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
}