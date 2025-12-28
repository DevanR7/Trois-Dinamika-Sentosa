<?php

namespace App\Events;

use App\Models\SalesInvoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SalesInvoice $invoice;

    /**
     * Create a new event instance.
     */
    public function __construct(SalesInvoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('invoice.' . $this->invoice->invoice_id),
        ];
    }

    /**
     * Nama event yang akan disiarkan.
     */
    public function broadcastAs(): string
    {
        return 'payment.updated';
    }

    /**
     * Data yang akan dikirim bersama siaran.
     * Ini adalah informasi yang dibutuhkan frontend untuk update UI.
     */
    public function broadcastWith(): array
    {
        $this->invoice->refresh();

        $totalRetur = $this->invoice->returns->sum('total_amount');
        $pendingAmount = $this->invoice->payments->where('status', 'pending_verification')->sum('amount');
        $sisaTagihan = $this->invoice->total_amount - $this->invoice->amount_paid - $totalRetur;

        return [
            'invoice_id' => $this->invoice->invoice_id,
            'status' => $this->invoice->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->invoice->status)),
            'amount_paid_formatted' => 'Rp ' . number_format($this->invoice->amount_paid, 0, ',', '.'),
            'pending_amount_formatted' => 'Rp ' . number_format($pendingAmount, 0, ',', '.'),
            'remaining_balance_formatted' => 'Rp ' . number_format($sisaTagihan, 0, ',', '.'),
            'can_pay' => ($this->invoice->amount_paid + $pendingAmount) < ($this->invoice->total_amount - $totalRetur),
        ];
    }
}