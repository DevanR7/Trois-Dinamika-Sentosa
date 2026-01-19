<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ValidatesAccountingPeriod; // Gunakan trait tutup buku

class StoreSalesInvoiceRequest extends FormRequest
{
    use ValidatesAccountingPeriod;

    public function authorize(): bool
    {
        return $this->user()->can('create-invoices');
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:order_date',
            'sales_order_id' => 'nullable|exists:orders,order_id',
            
            // Produk Items
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.custom_price' => 'required|numeric|min:0', 
            'products.*.update_master_price' => 'nullable|boolean', 
            
            // Biaya Tambahan
            'additional_costs' => 'nullable|array',
            'additional_costs.*.description' => 'required_with:additional_costs|string|max:255',
            'additional_costs.*.amount' => 'required_with:additional_costs|numeric|min:0',
            
            // Pajak & Diskon
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'taxes' => 'nullable|array',
            'taxes.*' => 'exists:taxes,id',
            
            // Lainnya
            'notes' => 'nullable|string',
            'user_id_sales' => 'nullable|exists:users,user_id',
        ];
    }

    // Custom Validation logic setelah rules standar lolos
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Cek Tutup Buku
            if ($this->isDateClosed($this->order_date)) {
                $validator->errors()->add('order_date', 'Tanggal invoice berada dalam periode tahun buku yang sudah ditutup.');
            }
        });
    }

    public function messages()
    {
        return [
            'products.required' => 'Minimal harus ada 1 produk.',
            'products.*.quantity.min' => 'Jumlah produk tidak boleh 0.',
        ];
    }
}