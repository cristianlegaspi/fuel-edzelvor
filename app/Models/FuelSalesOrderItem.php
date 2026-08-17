<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelSalesOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fuel_sales_order_id',
        'fuel_product',
        'unit_price',
        'quantity_liters',
        'sold_liters',
        'remaining_liters',
        'line_total_amount',
        'remarks',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity_liters' => 'decimal:2',
        'sold_liters' => 'decimal:2',
        'remaining_liters' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (FuelSalesOrderItem $item) {
            $item->fuel_product = strtoupper((string) $item->fuel_product);

            $item->line_total_amount = round(
                (float) $item->unit_price * (float) $item->quantity_liters,
                2
            );
        });

        static::saved(function (FuelSalesOrderItem $item) {
            $item->salesOrder?->recalculateTotals();
            $item->salesOrder?->recalculateStocks();
        });

        static::deleted(function (FuelSalesOrderItem $item) {
            $item->salesOrder?->recalculateTotals();
            $item->salesOrder?->recalculateStocks();
        });

        static::restored(function (FuelSalesOrderItem $item) {
            $item->salesOrder?->recalculateTotals();
            $item->salesOrder?->recalculateStocks();
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(FuelSalesOrder::class, 'fuel_sales_order_id');
    }
}