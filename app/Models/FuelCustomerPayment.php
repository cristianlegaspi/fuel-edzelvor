<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelCustomerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_payment_tracking_no',
        'fuel_customer_purchase_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (FuelCustomerPayment $payment) {
            if (! $payment->customer_payment_tracking_no) {
                $payment->updateQuietly([
                    'customer_payment_tracking_no' => self::generateTrackingNo($payment),
                ]);
            }

            $payment->customerPurchase?->recalculateTotals();
        });

        static::saved(function (FuelCustomerPayment $payment) {
            $payment->customerPurchase?->recalculateTotals();
        });

        static::deleted(function (FuelCustomerPayment $payment) {
            $payment->customerPurchase?->recalculateTotals();
        });

        static::restored(function (FuelCustomerPayment $payment) {
            $payment->customerPurchase?->recalculateTotals();
        });
    }

    public static function generateTrackingNo(FuelCustomerPayment $payment): string
    {
        $date = $payment->created_at
            ? $payment->created_at->format('Ymd')
            : now()->format('Ymd');

        return 'CPAY-' . $date . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    public function customerPurchase(): BelongsTo
    {
        return $this->belongsTo(FuelCustomerPurchase::class, 'fuel_customer_purchase_id');
    }
}