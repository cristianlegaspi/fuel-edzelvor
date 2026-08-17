<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class FuelCustomerPurchaseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fuel_customer_purchase_id',
        'fuel_product',
        'liters',
        'freight_alwin',
        'freight_tanker',
        'freight_040',
        'amount_per_liter',
        'subtotal_without_freight',
        'payable_to_supplier',
        'subtotal_with_freight',
        'selling_price',
        'subtotal_selling_price',
        'ewt_rate',
        'less_ewt_rate',
        'payables',
        'net_income',
        'remarks',
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'freight_alwin' => 'decimal:3',
        'freight_tanker' => 'decimal:3',
        'freight_040' => 'decimal:3',
        'amount_per_liter' => 'decimal:3',
        'subtotal_without_freight' => 'decimal:2',
        'payable_to_supplier' => 'decimal:2',
        'subtotal_with_freight' => 'decimal:2',
        'selling_price' => 'decimal:3',
        'subtotal_selling_price' => 'decimal:2',
        'ewt_rate' => 'decimal:5',
        'less_ewt_rate' => 'decimal:2',
        'payables' => 'decimal:2',
        'net_income' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (FuelCustomerPurchaseItem $item): void {
            $item->fuel_product = strtoupper((string) $item->fuel_product);

            $liters = (float) ($item->liters ?? 0);

            self::validateAvailableStock($item, $liters);

            $freightAlwin = (float) ($item->freight_alwin ?? 0);
            $freightTanker = (float) ($item->freight_tanker ?? 0);
            $freight040 = (float) ($item->freight_040 ?? 0);

            $amountPerLiter = (float) ($item->amount_per_liter ?? 0);
            $sellingPrice = (float) ($item->selling_price ?? 0);
            $ewtRate = (float) ($item->ewt_rate ?? 0);

            $vatDivisor = 1.12;

            $totalFreightPerLiter = $freightAlwin + $freightTanker + $freight040;

            $subtotalWithoutFreight = round($liters * $amountPerLiter, 2);

            $subtotalWithFreight = round(
                $subtotalWithoutFreight + ($liters * $totalFreightPerLiter),
                2
            );

            $subtotalSellingPrice = round($liters * $sellingPrice, 2);

            $lessEwtRate = 0;

            if ($subtotalSellingPrice > 0 && $ewtRate > 0) {
                $lessEwtRate = round(($subtotalSellingPrice / $vatDivisor) * $ewtRate, 2);
            }

            $payables = round($subtotalSellingPrice - $lessEwtRate, 2);

            /*
            |--------------------------------------------------------------------------
            | Item Net Income
            |--------------------------------------------------------------------------
            | Item Net Income = Payables - Sub-total w/ Freight
            |
            | The main purchase net income will deduct Garage, Agent Comm,
            | Receiver, and Others Amount inside FuelCustomerPurchase model.
            */
            $netIncome = round($payables - $subtotalWithFreight, 2);

            $item->subtotal_without_freight = $subtotalWithoutFreight;
            $item->payable_to_supplier = $subtotalWithFreight;
            $item->subtotal_with_freight = $subtotalWithFreight;
            $item->subtotal_selling_price = $subtotalSellingPrice;
            $item->less_ewt_rate = $lessEwtRate;
            $item->payables = $payables;
            $item->net_income = $netIncome;
        });

        static::saved(function (FuelCustomerPurchaseItem $item): void {
            $item->customerPurchase?->recalculateTotals();
            $item->customerPurchase?->salesOrder?->recalculateStocks();

            /*
            |--------------------------------------------------------------------------
            | If item was moved to another customer purchase
            |--------------------------------------------------------------------------
            | Recalculate the old purchase also.
            */
            if ($item->wasChanged('fuel_customer_purchase_id')) {
                $oldPurchaseId = $item->getOriginal('fuel_customer_purchase_id');

                if ($oldPurchaseId && $oldPurchaseId != $item->fuel_customer_purchase_id) {
                    $oldPurchase = FuelCustomerPurchase::query()->find($oldPurchaseId);

                    $oldPurchase?->recalculateTotals();
                    $oldPurchase?->salesOrder?->recalculateStocks();
                }
            }
        });

        static::deleted(function (FuelCustomerPurchaseItem $item): void {
            $item->customerPurchase?->recalculateTotals();
            $item->customerPurchase?->salesOrder?->recalculateStocks();
        });

        static::restored(function (FuelCustomerPurchaseItem $item): void {
            $item->customerPurchase?->recalculateTotals();
            $item->customerPurchase?->salesOrder?->recalculateStocks();
        });
    }

    protected static function validateAvailableStock(FuelCustomerPurchaseItem $item, float $liters): void
    {
        if ($liters <= 0) {
            return;
        }

        $purchase = $item->customerPurchase;

        if (! $purchase || ! $purchase->fuel_sales_order_id || ! $item->fuel_product) {
            return;
        }

        $fuelProduct = strtoupper((string) $item->fuel_product);

        $supplierItem = FuelSalesOrderItem::query()
            ->where('fuel_sales_order_id', $purchase->fuel_sales_order_id)
            ->where('fuel_product', $fuelProduct)
            ->first();

        if (! $supplierItem) {
            throw ValidationException::withMessages([
                'fuel_product' => "No available stock found for {$fuelProduct} in the selected supplier order.",
            ]);
        }

        $alreadySoldQuery = FuelCustomerPurchaseItem::query()
            ->where('fuel_product', $fuelProduct)
            ->whereHas('customerPurchase', function ($query) use ($purchase): void {
                $query->where('fuel_sales_order_id', $purchase->fuel_sales_order_id);
            });

        if ($item->exists) {
            $alreadySoldQuery->where('id', '!=', $item->id);
        }

        $alreadySold = (float) $alreadySoldQuery->sum('liters');

        $originalStock = (float) ($supplierItem->quantity_liters ?? 0);

        $availableStock = round($originalStock - $alreadySold, 2);

        if ($liters > $availableStock) {
            throw ValidationException::withMessages([
                'liters' => "Not enough {$fuelProduct} stock. Available stock is only " . number_format($availableStock, 2) . " liters.",
            ]);
        }
    }

    public function customerPurchase(): BelongsTo
    {
        return $this->belongsTo(FuelCustomerPurchase::class, 'fuel_customer_purchase_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(FuelCustomerPurchase::class, 'fuel_customer_purchase_id');
    }
}