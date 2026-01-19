<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;

class SalesInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'invoice_id';

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
        'status', // draft, unpaid, partially_paid, paid, overdue, cancelled
        'pending_snap_token',
        'pending_snap_expires_at',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'float',
        'subtotal' => 'float',
        'discount_percentage' => 'float',
        'discount_amount' => 'float',
        'amount_paid' => 'float',
        'pending_snap_expires_at' => 'datetime',
    ];

    // --- RELASI ---

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

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id', 'invoice_id');
    }

    public function deductingReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id', 'invoice_id')
                    ->where('return_handling_type', 'deduct_invoice');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'sales_invoice_id', 'invoice_id');
    }

    public function additionalCosts()
    {
        return $this->hasMany(InvoiceAdditionalCost::class, 'invoice_id', 'invoice_id');
    }

    // --- LOGIKA UTAMA (FIX DOUBLE COUNTING) ---

    public function getRemainingBalanceAttribute(): float
    {
        // 1. Ambil Total Invoice Terkini (Sudah hasil revisi)
        $total = $this->total_amount; 

        // 2. Hitung Adjustment (HANYA MANUAL)
        // Filter `is_calculation_adjustment` = true.
        // Revisi otomatis ditandai false, jadi tidak akan dijumlahkan lagi.
        
        if ($this->relationLoaded('adjustments')) {
            $totalAdjustments = $this->adjustments
                ->where('is_calculation_adjustment', true) 
                ->sum(function($adj) {
                    return $adj->type == 'debit_note' ? $adj->amount : -$adj->amount;
                });
        } else {
            $totalDebit = $this->adjustments()
                ->where('is_calculation_adjustment', true)
                ->where('type', 'debit_note')->sum('amount');
                
            $totalCredit = $this->adjustments()
                ->where('is_calculation_adjustment', true)
                ->where('type', 'credit_note')->sum('amount');
                
            $totalAdjustments = $totalDebit - $totalCredit;
        }

        // 3. Hitung Retur (Potong Tagihan)
        if ($this->relationLoaded('deductingReturns')) {
            $totalReturns = $this->deductingReturns->sum('total_amount');
        } else {
            $totalReturns = $this->deductingReturns()->sum('total_amount');
        }

        // 4. Hitung Kewajiban Bersih
        $netObligation = $total + $totalAdjustments - $totalReturns;

        // 5. Kurangi Pembayaran Masuk
        $totalPaid = $this->payments()->where('status', 'completed')->sum('amount');

        // Hasil akhir (bisa negatif jika overpayment)
        return round($netObligation - $totalPaid, 2);
    }

    public function updatePaymentStatus()
    {
        if ($this->status == 'cancelled') return;

        // Gunakan logic remaining balance yang baru
        $sisaTagihan = $this->remaining_balance;
        
        // Toleransi selisih koma 0.01
        if ($sisaTagihan <= 0.01) { 
            // Lunas (Termasuk jika minus/overpaid)
            $newStatus = 'paid';
        } elseif ($this->amount_paid > 0.01) {
            $newStatus = 'partially_paid';
        } else {
            $newStatus = 'unpaid';
        }

        // Hitung total paid aktual untuk disimpan di DB
        $totalPaid = $this->payments()->where('status', 'completed')->sum('amount');

        if ($this->status != $newStatus || abs($this->amount_paid - $totalPaid) > 0.01) {
            $this->update([
                'status' => $newStatus,
                'amount_paid' => $totalPaid
            ]);
            
            if ($newStatus === 'paid') {
                $this->update([
                    'pending_snap_token' => null, 
                    'pending_snap_expires_at' => null
                ]);
            }
        }

        // Sinkronisasi Status Deposit Retur
        if ($newStatus === 'paid') {
            // Rilis Deposit Pending
            ClientLedger::where('sales_invoice_id', $this->invoice_id)
                ->where('status', 'pending')
                ->where('type', 'credit')
                ->where('reference_type', SalesReturn::class)
                ->update([
                    'status' => 'available',
                    'description' => DB::raw("REPLACE(description, '(Tertahan: Menunggu Pelunasan Invoice)', '(Rilis: Invoice Lunas)')")
                ]);
        } else {
            // Tahan Kembali Deposit
            ClientLedger::where('sales_invoice_id', $this->invoice_id)
                ->where('status', 'available')
                ->where('type', 'credit')
                ->where('reference_type', SalesReturn::class)
                ->update([
                    'status' => 'pending',
                    'description' => DB::raw("CONCAT(REPLACE(description, '(Rilis: Invoice Lunas)', ''), ' (Tertahan: Menunggu Pelunasan Invoice)')")
                ]);
        }
    }

    public static function generateInvoiceNumber($salesUserId = null, $orderSource = null): string
    {
        $yearMonth = now()->format('Ym');
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $counterGroup = 'GENERAL';
        $invoiceSuffix = '';

        if ($salesUserId) {
            $sales = User::find($salesUserId);
            if ($sales && !empty($sales->sales_code)) {
                $code = strtoupper($sales->sales_code);
                $counterGroup = $code;
                $invoiceSuffix = '/' . $code;
            }
        } elseif ($orderSource === 'client') {
            $counterGroup = 'ONLINE';
            $invoiceSuffix = '/OL';
        }

        return DB::transaction(function() use ($yearMonth, $counterGroup, $year, $month, $invoiceSuffix) {
            $counter = DB::table('invoice_counters')
                ->where('ym', $yearMonth)
                ->where('counter_group', $counterGroup)
                ->lockForUpdate()
                ->first();
            
            if ($counter) {
                $nextSequence = $counter->last_sequence + 1;
                DB::table('invoice_counters')
                    ->where('id', $counter->id)
                    ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
            } else {
                $nextSequence = 1;
                try {
                    DB::table('invoice_counters')->insert([
                        'ym' => $yearMonth, 
                        'counter_group' => $counterGroup, 
                        'last_sequence' => $nextSequence, 
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                     return self::generateInvoiceNumber(null, null); 
                }
            }
            $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "INV/{$year}/{$month}/{$sequencePadded}{$invoiceSuffix}";
        });
    }
}