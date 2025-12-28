<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use App\Models\InvoiceItem;
use App\Models\Tax;
use App\Models\Payment;
use App\Models\SalesReturn;
use App\Models\InvoiceAdjustment;
use App\Models\ClientLedger;
use App\Models\InvoiceAdditionalCost;
use App\Events\PaymentStatusUpdated;

class SalesInvoice extends Model
{
    use HasFactory;
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
        'status',
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
        'pending_snap_expires_at' => 'datetime',
    ];

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
        } 
        elseif ($orderSource === 'client') {
            $counterGroup = 'ONLINE'; 
            $invoiceSuffix = '/OL'; 
        }

        $counter = DB::table('invoice_counters')
            ->where('ym', $yearMonth)
            ->where('counter_group', $counterGroup)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            $nextSequence = $counter->last_sequence + 1;
            DB::table('invoice_counters')
                ->where('ym', $yearMonth)
                ->where('counter_group', $counterGroup)
                ->update(['last_sequence' => $nextSequence, 'updated_at' => now()]);
        } else {
            $nextSequence = 1;
            DB::table('invoice_counters')->insert([
                'ym' => $yearMonth,
                'counter_group' => $counterGroup, 
                'last_sequence' => $nextSequence,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT); 
        return "INV/{$year}/{$month}/{$sequencePadded}{$invoiceSuffix}";
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

    public function getTotalDeductingReturnsAttribute(): float
    {
        return $this->deductingReturns()->sum('total_amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        $balance = $this->total_due - $this->amount_paid;
        return max(0, $balance);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'sales_invoice_id', 'invoice_id');
    }

    public function updatePaymentStatus()
    {
        if ($this->status == 'cancelled') {
            return;
        }
        $totalPaid = $this->payments()->where('status', 'completed')->sum('amount');
        $this->loadMissing(['adjustments', 'deductingReturns']);
        $totalDue = $this->total_due;
        $totalDue = round($totalDue, 2);
        $totalPaid = round($totalPaid, 2);
        $newStatus = 'unpaid'; 
        
        if ($totalPaid >= ($totalDue - 0.01)) { 
            $newStatus = 'paid';
        } elseif ($totalPaid > 0.01) {
            $newStatus = 'partially_paid';
        }
        
        if ($this->status != $newStatus || $this->amount_paid != $totalPaid) {
            $this->update([
                'status' => $newStatus,
                'amount_paid' => $totalPaid 
            ]);

            PaymentStatusUpdated::dispatch($this);
        }
        
        if ($newStatus == 'paid') {
            if ($this->pending_snap_token) {
                $this->update([
                    'pending_snap_token' => null,
                    'pending_snap_expires_at' => null
                ]);
            }
            ClientLedger::where('sales_invoice_id', $this->invoice_id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'available',
                            'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                        ]);
        }
    }

    public function getTotalDueAttribute(): float
    {
        $total = $this->total_amount;
        if ($this->relationLoaded('adjustments')) {
            $totalAdjustments = $this->adjustments->sum(function($adj) {
                return $adj->type == 'debit_note' ? $adj->amount : -$adj->amount;
            });
        } else {
            $totalDebitNotes = $this->adjustments()->where('type', 'debit_note')->sum('amount');
            $totalCreditNotes = $this->adjustments()->where('type', 'credit_note')->sum('amount');
            $totalAdjustments = $totalDebitNotes - $totalCreditNotes;
        }
        $total += $totalAdjustments;
        if ($this->relationLoaded('deductingReturns')) {
            $total -= $this->deductingReturns->sum('total_amount');
        } else {
            $total -= $this->total_deducting_returns;
        }
        return (float) $total;
    }

    public function additionalCosts()
    {
        return $this->hasMany(InvoiceAdditionalCost::class, 'invoice_id', 'invoice_id');
    }
}