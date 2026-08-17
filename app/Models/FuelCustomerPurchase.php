<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FuelCustomerPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fuel_sales_order_id',
        'date_ordered',
        'sales_order_no',
        'supplier',
        'customer',
        'tanker_details',
        'order_no_details',
        'total_liters',
        'total_subtotal_without_freight',
        'total_payable_to_supplier',
        'total_subtotal_with_freight',
        'total_selling_amount',
        'total_less_ewt',
        'total_payables',
        'garage',
        'agent_comm',
        'receiver',
        'others_amount',
        'others_comment',
        'net_income',
        'balance_short_over',
        'payment_amount',
        'date_of_payment',
        'payment_details',
        'cheque_details',
        'atl_date',
        'atl_no',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date_ordered' => 'date',
        'atl_date' => 'date',
        'date_of_payment' => 'date',
        'total_liters' => 'decimal:2',
        'total_subtotal_without_freight' => 'decimal:2',
        'total_payable_to_supplier' => 'decimal:2',
        'total_subtotal_with_freight' => 'decimal:2',
        'total_selling_amount' => 'decimal:2',
        'total_less_ewt' => 'decimal:2',
        'total_payables' => 'decimal:2',
        'garage' => 'decimal:2',
        'agent_comm' => 'decimal:2',
        'receiver' => 'decimal:2',
        'others_amount' => 'decimal:2',
        'net_income' => 'decimal:2',
        'balance_short_over' => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (FuelCustomerPurchase $purchase): void {
            $fuelSalesOrderChanged = $purchase->wasChanged(
                'fuel_sales_order_id'
            );

            $oldSalesOrderId = $purchase->getOriginal(
                'fuel_sales_order_id'
            );

            $purchase->recalculateTotals();

            $purchase->salesOrder?->recalculateStocks();

            /*
        |--------------------------------------------------------------------------
        | Automatically manage the tanker delivery record
        |--------------------------------------------------------------------------
        */
            if (filled($purchase->tanker_details)) {
                $tankerRecord = FuelTankerRecord::withTrashed()
                    ->firstOrCreate([
                        'fuel_customer_purchase_id' => $purchase->id,
                    ]);

                if ($tankerRecord->trashed()) {
                    $tankerRecord->restore();
                }

                $tankerRecord->recalculateNetIncome();
            } else {
                $purchase->tankerRecord?->delete();
            }

            if (
                $fuelSalesOrderChanged &&
                $oldSalesOrderId &&
                $oldSalesOrderId != $purchase->fuel_sales_order_id
            ) {
                FuelSalesOrder::query()
                    ->find($oldSalesOrderId)
                    ?->recalculateStocks();
            }
        });

        static::deleted(function (FuelCustomerPurchase $purchase): void {
            $purchase->salesOrder?->recalculateStocks();

            $purchase->tankerRecord?->delete();
        });

        static::restored(function (FuelCustomerPurchase $purchase): void {
            $purchase->recalculateTotals();

            $purchase->salesOrder?->recalculateStocks();

            if (filled($purchase->tanker_details)) {
                $tankerRecord = FuelTankerRecord::withTrashed()
                    ->firstOrCreate([
                        'fuel_customer_purchase_id' => $purchase->id,
                    ]);

                if ($tankerRecord->trashed()) {
                    $tankerRecord->restore();
                }

                $tankerRecord->recalculateNetIncome();
            }
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(FuelSalesOrder::class, 'fuel_sales_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany('App\Models\FuelCustomerPurchaseItem', 'fuel_customer_purchase_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany('App\Models\FuelCustomerPayment', 'fuel_customer_purchase_id');
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $totalLiters = 0;
        $totalSubtotalWithoutFreight = 0;
        $totalPayableToSupplier = 0;
        $totalSubtotalWithFreight = 0;
        $totalSellingAmount = 0;
        $totalLessEwt = 0;
        $totalPayables = 0;

        foreach ($items as $item) {
            $liters = (float) ($item->liters ?? 0);
            $freightAlwin = (float) ($item->freight_alwin ?? 0);
            $freightTanker = (float) ($item->freight_tanker ?? 0);
            $freight040 = (float) ($item->freight_040 ?? 0);
            $amountPerLiter = (float) ($item->amount_per_liter ?? 0);
            $sellingPrice = (float) ($item->selling_price ?? 0);
            $ewtRate = (float) ($item->ewt_rate ?? 0);

            $subtotalWithoutFreight = round($liters * $amountPerLiter, 2);

            $subtotalWithFreight = round(
                $subtotalWithoutFreight + ($liters * ($freightAlwin + $freightTanker + $freight040)),
                2
            );

            $subtotalSellingPrice = round($liters * $sellingPrice, 2);

            $lessEwt = 0;

            if ($subtotalSellingPrice > 0 && $ewtRate > 0) {
                $lessEwt = round(($subtotalSellingPrice / 1.12) * $ewtRate, 2);
            }

            $payables = round($subtotalSellingPrice - $lessEwt, 2);

            $itemNetIncome = round($payables - $subtotalWithFreight, 2);

            $item->forceFill([
                'subtotal_without_freight' => $subtotalWithoutFreight,
                'payable_to_supplier' => $subtotalWithFreight,
                'subtotal_with_freight' => $subtotalWithFreight,
                'subtotal_selling_price' => $subtotalSellingPrice,
                'less_ewt_rate' => $lessEwt,
                'payables' => $payables,
                'net_income' => $itemNetIncome,
            ])->saveQuietly();

            $totalLiters += $liters;
            $totalSubtotalWithoutFreight += $subtotalWithoutFreight;
            $totalPayableToSupplier += $subtotalWithFreight;
            $totalSubtotalWithFreight += $subtotalWithFreight;
            $totalSellingAmount += $subtotalSellingPrice;
            $totalLessEwt += $lessEwt;
            $totalPayables += $payables;
        }

        $paymentAmount = round((float) $this->payments()->sum('amount'), 2);

        $garage = (float) ($this->garage ?? 0);
        $agentComm = (float) ($this->agent_comm ?? 0);
        $receiver = (float) ($this->receiver ?? 0);
        $othersAmount = (float) ($this->others_amount ?? 0);

        $totalExpenses = round($garage + $agentComm + $receiver + $othersAmount, 2);

        /*
        |--------------------------------------------------------------------------
        | Net Income
        |--------------------------------------------------------------------------
        | Net Income = Total Payables - Sub-total w/ Freight - Expenses
        */
        $netIncome = round($totalPayables - $totalSubtotalWithFreight - $totalExpenses, 2);

        /*
        |--------------------------------------------------------------------------
        | Balance / Short / Over
        |--------------------------------------------------------------------------
        | Negative = remaining balance
        | Zero = fully paid
        | Positive = overpayment
        */
        $balanceShortOver = round($paymentAmount - $totalPayables, 2);

        if ($totalPayables <= 0) {
            $status = $paymentAmount > 0 ? 'overpaid' : 'unpaid';
        } elseif ($paymentAmount <= 0) {
            $status = 'unpaid';
        } elseif ($paymentAmount < $totalPayables) {
            $status = 'partial';
        } elseif ($paymentAmount == $totalPayables) {
            $status = 'paid';
        } else {
            $status = 'overpaid';
        }

        $this->forceFill([
            'total_liters' => round($totalLiters, 2),
            'total_subtotal_without_freight' => round($totalSubtotalWithoutFreight, 2),
            'total_payable_to_supplier' => round($totalPayableToSupplier, 2),
            'total_subtotal_with_freight' => round($totalSubtotalWithFreight, 2),
            'total_selling_amount' => round($totalSellingAmount, 2),
            'total_less_ewt' => round($totalLessEwt, 2),
            'total_payables' => round($totalPayables, 2),
            'payment_amount' => $paymentAmount,
            'net_income' => $netIncome,
            'balance_short_over' => $balanceShortOver,
            'status' => $status,
        ])->saveQuietly();
    }

    public function tankerRecord(): HasOne
    {
        return $this->hasOne(
            FuelTankerRecord::class,
            'fuel_customer_purchase_id'
        );
    }
}
