<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelCustomerPayment extends Model
{
    use Auditable, SoftDeletes;

    /** Temporary audit context, never stored as a payment attribute. */
    public ?string $auditEditReason = null;

    protected $fillable = [
        'customer_payment_tracking_no', 'fuel_customer_purchase_id',
        'payment_date', 'amount', 'payment_method', 'reference_no',
        'remarks', 'proof_image',
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::created(function (self $payment) {
            if (!$payment->customer_payment_tracking_no) {
                $payment->updateQuietly(['customer_payment_tracking_no' => self::generateTrackingNo($payment)]);
            }
            $payment->customerPurchase?->recalculateTotals();
        });
        static::saved(fn(self $payment) => $payment->customerPurchase?->recalculateTotals());
        static::deleted(fn(self $payment) => $payment->customerPurchase?->recalculateTotals());
        static::restored(fn(self $payment) => $payment->customerPurchase?->recalculateTotals());
    }

    public static function generateTrackingNo(self $payment): string
    {
        $date = $payment->created_at?->format('Ymd') ?? now()->format('Ymd');
        return 'CPAY-'.$date.'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    public function customerPurchase(): BelongsTo
    {
        return $this->belongsTo(FuelCustomerPurchase::class, 'fuel_customer_purchase_id');
    }
}
