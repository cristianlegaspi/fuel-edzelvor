<?php

namespace Database\Seeders;

use App\Models\FuelCustomerPayment;
use App\Models\FuelCustomerPurchase;
use App\Models\FuelCustomerPurchaseItem;
use App\Models\FuelSalesOrder;
use Illuminate\Database\Seeder;

class FuelCustomerPurchaseSeeder extends Seeder
{
    /**
     * Seed customer purchases against the supplier orders created by
     * FuelSalesOrderSeeder. Liters per item stay within each supplier
     * order's remaining stock, since FuelCustomerPurchaseItem validates
     * that on save.
     *
     * Any purchase with a non-empty tanker_details value automatically
     * gets a FuelTankerRecord created for it (see FuelCustomerPurchase::booted()),
     * so we just fill in the driver/expense fields on that record afterward.
     */
    public function run(): void
    {
        $order1 = FuelSalesOrder::where('sales_order_no', 'SO-2026-0001')->first();
        $order2 = FuelSalesOrder::where('sales_order_no', 'SO-2026-0002')->first();
        $order3 = FuelSalesOrder::where('sales_order_no', 'SO-2026-0003')->first();

        if (! $order1 || ! $order2 || ! $order3) {
            $this->command?->warn('Skipping FuelCustomerPurchaseSeeder: run FuelSalesOrderSeeder first.');

            return;
        }

        $purchases = [
            [
                'order' => $order1,
                'customer' => 'Dela Cruz Gasoline Station',
                'order_no_details' => 'PO-1001',
                'tanker_details' => 'Tanker A - ABC-1234',
                'items' => [
                    ['fuel_product' => 'DIESEL', 'liters' => 2000, 'freight_alwin' => 0.150, 'freight_tanker' => 0.200, 'amount_per_liter' => 47.000, 'selling_price' => 49.500, 'ewt_rate' => 0.01],
                    ['fuel_product' => 'GASOLINE', 'liters' => 1000, 'freight_alwin' => 0.150, 'freight_tanker' => 0.200, 'amount_per_liter' => 53.500, 'selling_price' => 56.000, 'ewt_rate' => 0.01],
                ],
                'expenses' => ['garage' => 500, 'agent_comm' => 300, 'receiver' => 200, 'others_amount' => 0],
                'payments' => [
                    ['payment_date' => '2026-05-18', 'amount' => 60000, 'payment_method' => 'Bank Transfer', 'reference_no' => 'CPAY-BT-0001'],
                ],
                'tanker' => ['driver_name' => 'Ramon Santos', 'driver_salary' => 1500, 'other_expenses' => 300, 'other_expenses_details' => 'Toll & parking'],
            ],
            [
                'order' => $order2,
                'customer' => 'JR Diesel Traders',
                'order_no_details' => 'PO-1002',
                'tanker_details' => null,
                'items' => [
                    ['fuel_product' => 'DIESEL', 'liters' => 1500, 'freight_alwin' => 0.100, 'freight_tanker' => 0.000, 'amount_per_liter' => 47.500, 'selling_price' => 50.000, 'ewt_rate' => 0.01],
                    ['fuel_product' => 'KEROSENE', 'liters' => 800, 'freight_alwin' => 0.100, 'freight_tanker' => 0.000, 'amount_per_liter' => 50.000, 'selling_price' => 53.500, 'ewt_rate' => 0.01],
                ],
                'expenses' => ['garage' => 0, 'agent_comm' => 200, 'receiver' => 0, 'others_amount' => 0],
                'payments' => [
                    ['payment_date' => '2026-06-02', 'amount' => 40000, 'payment_method' => 'Cash', 'reference_no' => 'CPAY-CSH-0002'],
                    ['payment_date' => '2026-06-10', 'amount' => 30000, 'payment_method' => 'Cash', 'reference_no' => 'CPAY-CSH-0003'],
                ],
                'tanker' => null,
            ],
            [
                'order' => $order3,
                'customer' => 'Metro Fuel Hauling',
                'order_no_details' => 'PO-1003',
                'tanker_details' => 'Tanker B - XYZ-5678',
                'items' => [
                    ['fuel_product' => 'DIESEL', 'liters' => 3000, 'freight_alwin' => 0.180, 'freight_tanker' => 0.220, 'amount_per_liter' => 46.500, 'selling_price' => 49.000, 'ewt_rate' => 0.01],
                ],
                'expenses' => ['garage' => 800, 'agent_comm' => 400, 'receiver' => 0, 'others_amount' => 0],
                // No payments -> stays "unpaid".
                'payments' => [],
                'tanker' => ['driver_name' => 'Bong Reyes', 'driver_salary' => 1800, 'other_expenses' => 0, 'other_expenses_details' => null],
            ],
        ];

        foreach ($purchases as $purchaseData) {
            /** @var FuelSalesOrder $order */
            $order = $purchaseData['order'];

            /** @var FuelCustomerPurchase $purchase */
            $purchase = FuelCustomerPurchase::query()->firstOrCreate(
                [
                    'fuel_sales_order_id' => $order->id,
                    'customer' => $purchaseData['customer'],
                    'order_no_details' => $purchaseData['order_no_details'],
                ],
                [
                    'date_ordered' => $order->date_ordered,
                    'sales_order_no' => $order->sales_order_no,
                    'supplier' => $order->supplier,
                    'tanker_details' => $purchaseData['tanker_details'],
                    'garage' => $purchaseData['expenses']['garage'],
                    'agent_comm' => $purchaseData['expenses']['agent_comm'],
                    'receiver' => $purchaseData['expenses']['receiver'],
                    'others_amount' => $purchaseData['expenses']['others_amount'],
                ]
            );

            foreach ($purchaseData['items'] as $itemData) {
                FuelCustomerPurchaseItem::query()->firstOrCreate(
                    [
                        'fuel_customer_purchase_id' => $purchase->id,
                        'fuel_product' => $itemData['fuel_product'],
                    ],
                    [
                        'liters' => $itemData['liters'],
                        'freight_alwin' => $itemData['freight_alwin'],
                        'freight_tanker' => $itemData['freight_tanker'],
                        'amount_per_liter' => $itemData['amount_per_liter'],
                        'selling_price' => $itemData['selling_price'],
                        'ewt_rate' => $itemData['ewt_rate'],
                    ]
                );
            }

            foreach ($purchaseData['payments'] as $paymentData) {
                FuelCustomerPayment::query()->firstOrCreate(
                    [
                        'fuel_customer_purchase_id' => $purchase->id,
                        'reference_no' => $paymentData['reference_no'],
                    ],
                    [
                        'payment_date' => $paymentData['payment_date'],
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                    ]
                );
            }

            // The tanker record was auto-created by FuelCustomerPurchase's
            // booted() hook the moment we saved it with tanker_details set.
            // Fill in the driver/expense fields on top of it here.
            if ($purchaseData['tanker'] !== null) {
                $purchase->tankerRecord?->update([
                    'date_delivered' => $order->date_ordered,
                    'driver_name' => $purchaseData['tanker']['driver_name'],
                    'driver_salary' => $purchaseData['tanker']['driver_salary'],
                    'other_expenses' => $purchaseData['tanker']['other_expenses'],
                    'other_expenses_details' => $purchaseData['tanker']['other_expenses_details'],
                ]);
            }
        }
    }
}
