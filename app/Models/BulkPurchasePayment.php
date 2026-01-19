<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;

class BulkPurchasePayment extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'bulk_purchase_payments';
    protected $primaryKey = 'bulk_purchase_payment_id'; 

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method_id',
        'company_bank_account_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchaseOrderPayment::class, 'bulk_purchase_payment_id', 'bulk_purchase_payment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public static function generateNumber(): string
{
    $ym = now()->format('Ym');
    $type = 'purchase';

    return DB::transaction(function() use ($ym, $type) {
        $counter = DB::table('bulk_payment_counters')
            ->where('ym', $ym)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('bulk_payment_counters')
                ->where('ym', $ym)
                ->where('type', $type)
                ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
        } else {
            try {
                $nextSequence = 1;
                DB::table('bulk_payment_counters')->insert([
                    'ym' => $ym, 'type' => $type, 
                    'last_sequence' => $nextSequence, 
                    'created_at' => now(), 'updated_at' => now()
                ]);
            } catch (\Exception $e) {
                $counter = DB::table('bulk_payment_counters')
                    ->where('ym', $ym)->where('type', $type)
                    ->lockForUpdate()->first();
                $nextSequence = $counter->last_sequence + 1;
                DB::table('bulk_payment_counters')
                    ->where('ym', $ym)->where('type', $type)
                    ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
            }
        }

        $seq = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        return "BULK/PO/" . now()->format('Y') . "/" . now()->format('m') . "/" . $seq;
    });
}
}