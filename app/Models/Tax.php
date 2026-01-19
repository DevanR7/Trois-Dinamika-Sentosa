<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SalesInvoice;
use App\Traits\LogsActivity;

class Tax extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; 

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