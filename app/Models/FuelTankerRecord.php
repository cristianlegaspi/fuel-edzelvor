<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelTankerRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fuel_customer_purchase_id',
        'date_delivered',
        'driver_name',
        'driver_salary',
        'date_paid_driver',
        'other_expenses',
        'other_expenses_details',
        'net_income',
        'remarks',
    ];

    protected $casts = [
        'date_delivered' => 'date',
        'date_paid_driver' => 'date',
        'driver_salary' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'net_income' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (FuelTankerRecord $record): void {
            $record->net_income = $record->calculateNetIncome();
        });
    }

    public function customerPurchase(): BelongsTo
    {
        return $this->belongsTo(
            FuelCustomerPurchase::class,
            'fuel_customer_purchase_id'
        );
    }

    public function getFreightIncome(): float
    {
        $purchase = $this->customerPurchase;

        if (! $purchase) {
            return 0;
        }

        return round(
            (float) $purchase->items->sum(function ($item): float {
                $liters = (float) ($item->liters ?? 0);
                $freightPerLiter = (float) ($item->freight_tanker ?? 0);

                return $liters * $freightPerLiter;
            }),
            2
        );
    }

    public function getFreightPerLiter(): float
    {
        $purchase = $this->customerPurchase;

        if (! $purchase) {
            return 0;
        }

        $totalLiters = (float) $purchase->items->sum(
            fn($item): float => (float) ($item->liters ?? 0)
        );

        if ($totalLiters <= 0) {
            return 0;
        }

        return round(
            $this->getFreightIncome() / $totalLiters,
            2
        );
    }

        public function calculateNetIncome(): float
        {
            return round(
                $this->getFreightIncome()
                - (float) ($this->driver_salary ?? 0)
                - (float) ($this->other_expenses ?? 0),
                2
            );
        }

    public function recalculateNetIncome(): void
    {
        $calculatedNetIncome = $this->calculateNetIncome();

        if (round((float) $this->net_income, 2) === $calculatedNetIncome) {
            return;
        }

        $this->forceFill([
            'net_income' => $calculatedNetIncome,
        ])->saveQuietly();
    }

  
}
