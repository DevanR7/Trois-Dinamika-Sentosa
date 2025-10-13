<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class Payment extends Model
    {
        use HasFactory;

        protected $primaryKey = 'payment_id';

        /**
         * The attributes that are mass assignable.
         *
         * @var array<int, string>
         */
        protected $fillable = [
            'invoice_id',
            'payment_date',
            'amount',
            'payment_method',
            'proof_of_payment_path',
            'transaction_id',
            'received_by_user_id',
            'status',
            'notes',
        ];

        /**
         * The attributes that should be cast.
         *
         * @var array<string, string>
         */
        protected $casts = [
            'payment_date' => 'date',
            'amount' => 'float',
        ];

        /**
         * Mendapatkan invoice yang terkait dengan pembayaran ini.
         */
        public function salesInvoice(): BelongsTo
        {
            return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
        }

        /**
         * Mendapatkan user yang menerima/memverifikasi pembayaran ini.
         */
        public function receivedBy(): BelongsTo
        {
            return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
        }
    }
