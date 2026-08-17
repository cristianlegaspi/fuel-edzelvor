<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelSalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date_ordered',
        'sales_order_no',
        'supplier',
        'ewt_rate',
        'vat_divisor',
        'total_liters',
        'gross_amount',
        'ewt_amount',
        'net_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date_ordered' => 'date',
        'ewt_rate' => 'decimal:5',
        'vat_divisor' => 'decimal:2',
        'total_liters' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'ewt_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FuelSalesOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FuelPayment::class);
    }

    public function customerPurchases(): HasMany
    {
        return $this->hasMany(FuelCustomerPurchase::class);
    }

    public function recalculateTotals(): void
    {
        $totalLiters = (float) $this->items()->sum('quantity_liters');
        $grossAmount = (float) $this->items()->sum('line_total_amount');

        $paidAmount = (float) $this->payments()->sum('amount');

        $ewtRate = (float) $this->ewt_rate;
        $vatDivisor = (float) ($this->vat_divisor ?: 1.12);

        $ewtAmount = round(($grossAmount / $vatDivisor) * $ewtRate, 2);
        $netAmount = round($grossAmount - $ewtAmount, 2);

        $balanceAmount = round($paidAmount - $netAmount, 2);

        $status = 'unpaid';

        if ($paidAmount > 0 && $paidAmount < $netAmount) {
            $status = 'partial';
        }

        if ($paidAmount >= $netAmount && $netAmount > 0) {
            $status = 'paid';
        }

        $this->updateQuietly([
            'total_liters' => $totalLiters,
            'gross_amount' => $grossAmount,
            'ewt_amount' => $ewtAmount,
            'net_amount' => $netAmount,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'status' => $status,
        ]);

        $this->recalculateStocks();
    }

    public function recalculateStocks(): void
    {
        $this->loadMissing('items');

        foreach ($this->items()->whereNull('deleted_at')->get() as $item) {
            $soldLiters = (float) FuelCustomerPurchaseItem::query()
                ->whereNull('deleted_at') // do not count soft-deleted customer purchase items
                ->where('fuel_product', strtoupper((string) $item->fuel_product))
                ->whereHas('customerPurchase', function ($query) {
                    $query->where('fuel_sales_order_id', $this->id)
                        ->whereNull('deleted_at'); // do not count soft-deleted customer purchases
                })
                ->sum('liters');

            $originalLiters = (float) $item->quantity_liters;
            $remainingLiters = round($originalLiters - $soldLiters, 2);

            $item->updateQuietly([
                'sold_liters' => round($soldLiters, 2),
                'remaining_liters' => $remainingLiters,
            ]);
        }
    }
}
