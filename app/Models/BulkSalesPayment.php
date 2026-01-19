<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;

class BulkSalesPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'bulk_sales_payments';
    protected $primaryKey = 'bulk_sales_payment_id';

    protected $fillable = [
        'payment_number',
        'client_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method_id',
        'company_bank_account_id',
        'notes',
        'status',
        'details', // WAJIB ADA: Menyimpan JSON array invoice ID
        'reference_number',
        'proof_of_payment_path',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
        'details' => 'array', // PENTING: Cast otomatis JSON ke Array PHP
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // --- RELASI ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id', 'user_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bulk_sales_payment_id', 'bulk_sales_payment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    // --- HELPER ---

    public static function generateNumber(): string
    {
        $ym = now()->format('Ym');
        $type = 'sales';

        return DB::transaction(function() use ($ym, $type) {
            $counter = DB::table('bulk_payment_counters')
                ->where('ym', $ym)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();
            
            $nextSeq = 1;
            if ($counter) {
                $nextSeq = $counter->last_sequence + 1;
                DB::table('bulk_payment_counters')
                    ->where('id', $counter->id)
                    ->update(['last_sequence' => $nextSeq, 'updated_at' => now()]);
            } else {
                try {
                    DB::table('bulk_payment_counters')->insert([
                        'ym' => $ym, 
                        'type' => $type, 
                        'last_sequence' => 1, 
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                     $counter = DB::table('bulk_payment_counters')->where('ym', $ym)->where('type', $type)->lockForUpdate()->first();
                     $nextSeq = $counter->last_sequence + 1;
                     DB::table('bulk_payment_counters')->where('id', $counter->id)->update(['last_sequence' => $nextSeq, 'updated_at' => now()]);
                }
            }
            $seq = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            return "BULK/SLS/" . now()->format('Y') . "/" . now()->format('m') . "/" . $seq;
        });
    }
}