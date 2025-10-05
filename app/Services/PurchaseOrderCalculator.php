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
        $subtotal = floatval($options['subtotal'] ?? 0);
        $applyDiscFee = !empty($options['apply_disc_fee']);
        $discFeePercent = isset($options['disc_fee_percent']) ? floatval($options['disc_fee_percent']) : null;
        $discFeeAmountOverride = isset($options['disc_fee_amount']) ? floatval($options['disc_fee_amount']) : null;

        $applyRounding = !empty($options['apply_rounding_discount']);
        $roundingAmount = isset($options['rounding_discount_amount']) ? floatval($options['rounding_discount_amount']) : 0;

        $useCustomDpp = !empty($options['use_custom_dpp_factor']);
        $customDppFactor = isset($options['custom_dpp_factor']) ? floatval($options['custom_dpp_factor']) : null;

        $shipping = floatval($options['shipping_amount'] ?? 0);

        // determine tax rate
        $taxRatePercent = floatval($options['default_tax_rate_percent'] ?? 0);
        if (!empty($options['tax_id'])) {
            $tax = Tax::find($options['tax_id']);
            if ($tax && $tax->is_active) {
                $taxRatePercent = floatval($tax->rate ?? $taxRatePercent);
            }
        }

        // compute disc fee amount
        $discFeeAmount = 0.0;
        if ($applyDiscFee) {
            if (!is_null($discFeePercent) && $discFeePercent > 0) {
                $discFeeAmount = ($discFeePercent / 100.0) * $subtotal;
            } elseif (!is_null($discFeeAmountOverride)) {
                $discFeeAmount = $discFeeAmountOverride;
            }
        }

        // rounding discount
        $roundDiscount = ($applyRounding ? floatval($roundingAmount) : 0.0);

        // taxable base (harga jual setelah diskon/pembulatan)
        $taxableBase = $subtotal - $discFeeAmount - $roundDiscount;
        if ($taxableBase < 0) $taxableBase = 0;

        // dpp factor default 11/12 (≈ 0.9166666667)
        $dppFactor = $useCustomDpp && $customDppFactor ? $customDppFactor : (11/12);

        // compute DPP & PPN (pembulatan ke rupiah paling dekat)
        $dpp = (float) round($taxableBase * $dppFactor);
        $ppn = (float) round($dpp * ($taxRatePercent / 100.0));

        // grand total: taxable base + ppn + shipping
        $grandTotal = (float) ($taxableBase + $ppn + $shipping);

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
