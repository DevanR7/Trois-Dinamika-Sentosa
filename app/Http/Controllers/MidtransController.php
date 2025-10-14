<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\PaymentGatewayCallback; // Import the new model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Illuminate\Support\Facades\Auth;

class MidtransController extends Controller
{
    public function __construct()
    {
        // Set Midtrans configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Handle the payment request from the client.
     */
    public function pay(Request $request, SalesInvoice $invoice)
    {
        // Validate the amount
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = $request->input('amount');
        $client = Auth::guard('client')->user();

        // Create a unique order ID for Midtrans
        // Format: INV-[invoice_id]-[timestamp]
        $orderId = 'INV-' . $invoice->invoice_id . '-' . time();

        // Prepare transaction details for Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $client->client_name,
                'email' => $client->email,
                'phone' => $client->phone_number,
            ],
        ];

        try {
            // Get Snap token from Midtrans
            $snapToken = Snap::getSnapToken($params);
            
            // Return the token as a JSON response
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle notifications from Midtrans (Webhook).
     */
    public function callback(Request $request)
    {
        // 1. Set up Midtrans server key
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');

        try {
            // 2. Get the notification from Midtrans
            $notification = new Notification();

            // 3. Extract key information
            $status = $notification->transaction_status;
            $orderId = $notification->order_id;
            $grossAmount = $notification->gross_amount;
            
            // 4. Save the raw callback data for auditing
            PaymentGatewayCallback::create([
                'invoice_id' => explode('-', $orderId)[1], // Extract invoice_id
                'vendor_transaction_id' => $notification->transaction_id,
                'status' => $status,
                'amount' => $grossAmount,
                'payment_type' => $notification->payment_type,
                'raw_response' => json_encode($notification->getResponse()),
            ]);

            // 5. Find the invoice
            $invoice = SalesInvoice::find(explode('-', $orderId)[1]);
            if (!$invoice) {
                // If invoice not found, it's an anomaly but we still acknowledge it
                return response()->json(['message' => 'Invoice not found but notification acknowledged.'], 200);
            }

            // 6. Check if transaction is successful
            if ($status == 'capture' || $status == 'settlement') {
                // Check to prevent double processing
                $isProcessed = Payment::where('transaction_id', $notification->transaction_id)->exists();

                if (!$isProcessed && $invoice->status !== 'paid') {
                    DB::transaction(function () use ($invoice, $notification, $grossAmount) {
                        // Create a new payment record
                        $invoice->payments()->create([
                            'payment_date'      => now(),
                            'amount'            => $grossAmount,
                            'payment_method'    => 'payment_gateway',
                            'status'            => 'completed',
                            'transaction_id'    => $notification->transaction_id,
                            'notes'             => 'Online payment via ' . $notification->payment_type,
                        ]);

                        // Update the invoice status
                        $invoice->amount_paid += $grossAmount;
                        if ($invoice->amount_paid >= $invoice->total_amount) {
                            $invoice->status = 'paid';
                        } else {
                            $invoice->status = 'partially_paid';
                        }
                        $invoice->save();
                    });
                }
            }

            // 7. Acknowledge the notification
            return response()->json(['message' => 'Notification processed successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}