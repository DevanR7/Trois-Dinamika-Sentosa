<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesInvoice;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];
    
    public function salesInvoices()
{
    return $this->belongsToMany(SalesInvoice::class, 'invoice_tax', 'tax_id', 'invoice_id');
}
}
