<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'po_id', 
        'received_by_user_id', 
        'payment_date', 
        'amount', 
        'payment_method', 
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    // ✅ TAMBAHKAN BAGIAN INI
    protected $casts = [
        'payment_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }
}
