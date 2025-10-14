<?php

namespace App\Events;


use App\Models\SalesInvoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $invoice;

    public function __construct(SalesInvoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('payments');
    }
}