<?php

namespace App\Services;

use App\Models\Tax;

class PurchaseOrderCalculator
{
    /**
     * $options:
     * - subtotal (required)
     * - apply_disc_fee (bool)
     * - disc_fee_percent (float|null)
     * - disc_fee_amount (float|null)
     * - apply_rounding_discount (bool)
     * - rounding_discount_amount (float|null)
     * - use_custom_dpp_factor (bool)
     * - custom_dpp_factor (float|null)
     * - tax_id (int|null)
     * - default_tax_rate_percent (float) // fallback
     * - shipping_amount (float)
     *
     * returns array with keys: subtotal, disc_fee_amount, rounding_discount_amount, taxable_base, dpp, ppn, grand_total, tax_rate_percent, dpp_factor, shipping_amount
     */
public static function calculate(array $options): array
    {
        // 1. Subtotal (Bulatkan ke Integer)
        $subtotal = round(floatval($options['subtotal'] ?? 0));

        $applyDiscFee = !empty($options['apply_disc_fee']);
        $discFeePercent = isset($options['disc_fee_percent']) ? floatval($options['disc_fee_percent']) : null;
        $discFeeAmountOverride = isset($options['disc_fee_amount']) ? floatval($options['disc_fee_amount']) : null;

        $applyRounding = !empty($options['apply_rounding_discount']);
        $roundingAmount = isset($options['rounding_discount_amount']) ? floatval($options['rounding_discount_amount']) : 0;

        $useCustomDpp = !empty($options['use_custom_dpp_factor']);
        $customDppFactor = isset($options['custom_dpp_factor']) ? floatval($options['custom_dpp_factor']) : null;

        $shipping = floatval($options['shipping_amount'] ?? 0);

        // Determine tax rate
        $taxRatePercent = floatval($options['default_tax_rate_percent'] ?? 0);
        if (!empty($options['tax_id'])) {
            $tax = Tax::find($options['tax_id']);
            if ($tax && $tax->is_active) {
                $taxRatePercent = floatval($tax->rate ?? $taxRatePercent);
            }
        }

        // 2. Compute Disc Fee Amount (Bulatkan ke Integer)
        $discFeeAmount = 0.0;
        if ($applyDiscFee) {
            if (!is_null($discFeePercent) && $discFeePercent > 0) {
                $discFeeAmount = ($discFeePercent / 100.0) * $subtotal;
            } elseif (!is_null($discFeeAmountOverride)) {
                $discFeeAmount = $discFeeAmountOverride;
            }
            $discFeeAmount = round($discFeeAmount); // FIX: Integer Rounding
        }

        // Rounding discount (Inputan user biasanya sudah bulat)
        $roundDiscount = ($applyRounding ? floatval($roundingAmount) : 0.0);

        // 3. Taxable Base (Harga Setelah Diskon)
        $taxableBase = $subtotal - $discFeeAmount - $roundDiscount;
        // Pastikan Taxable Base bulat (seharusnya sudah bulat karena komponennya bulat)
        $taxableBase = round($taxableBase);
        
        if ($taxableBase < 0) $taxableBase = 0;

        // 4. DPP Calculation (Bulatkan ke Integer)
        // Faktor DPP default 11/12 (approx 0.916666...)
        $dppFactor = $useCustomDpp && $customDppFactor ? $customDppFactor : (11/12);
        
        if ($useCustomDpp) {
            $dpp = $taxableBase * $dppFactor;
        } else {
            // Logic default: DPP = TaxableBase
            // (Kecuali Anda ingin menerapkan logic PPN Include secara default, 
            // tapi berdasarkan script JS sebelumnya, default DPP = Amount After Disc)
            $dpp = $taxableBase; 
        }
        
        $dpp = round($dpp); // FIX: Integer Rounding

        // 5. PPN Calculation (Bulatkan ke Integer)
        $ppn = round($dpp * ($taxRatePercent / 100.0)); // FIX: Integer Rounding

        // 6. Grand Total
        // Rumus: Base + PPN + Shipping
        $grandTotal = $taxableBase + $ppn + $shipping;

        return [
            'subtotal' => $subtotal,
            'disc_fee_amount' => $discFeeAmount,
            'rounding_discount_amount' => $roundDiscount,
            'taxable_base' => $taxableBase,
            'dpp' => $dpp,
            'ppn' => $ppn,
            'grand_total' => $grandTotal,
            'tax_rate_percent' => $taxRatePercent,
            'dpp_factor' => $dppFactor,
            'shipping_amount' => $shipping,
        ];
    }
}