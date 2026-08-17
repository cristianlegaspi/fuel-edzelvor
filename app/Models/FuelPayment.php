<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fuel_sales_order_id',
        'payment_date',
        'amount',
        'reference_no',
        'payment_method',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (FuelPayment $payment) {
            $payment->salesOrder?->recalculateTotals();
        });

        static::deleted(function (FuelPayment $payment) {
            $payment->salesOrder?->recalculateTotals();
        });

        static::restored(function (FuelPayment $payment) {
            $payment->salesOrder?->recalculateTotals();
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(FuelSalesOrder::class, 'fuel_sales_order_id');
    }
}