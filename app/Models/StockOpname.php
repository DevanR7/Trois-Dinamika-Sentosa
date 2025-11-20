<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockOpname extends Model
{
    use HasFactory;
    protected $primaryKey = 'opname_id';
    protected $fillable = ['opname_number', 'opname_date', 'notes', 'user_id', 'status', 'total_adjustment_value'];
    protected $casts = ['opname_date' => 'date', 'total_adjustment_value' => 'float'];

    public function items() {
        return $this->hasMany(StockOpnameItem::class, 'opname_id', 'opname_id');
    }
    
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public static function generateNumber() {
        $ym = now()->format('Ym');
        $last = self::where('opname_number', 'like', "SO-$ym-%")->orderBy('opname_id', 'desc')->first();
        $seq = $last ? intval(substr($last->opname_number, -3)) + 1 : 1;
        return "SO-$ym-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}