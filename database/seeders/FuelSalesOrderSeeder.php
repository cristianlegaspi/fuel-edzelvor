<?php

namespace Database\Seeders;

use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use App\Models\FuelSalesOrderItem;
use Illuminate\Database\Seeder;

class FuelSalesOrderSeeder extends Seeder
{
    /**
     * Seed supplier sales orders.
     *
     * Only "input" columns are set here (unit_price, quantity_liters,
     * payment amounts, etc.) — the computed columns (line_total_amount,
     * gross/net/paid/balance amounts, status, stock levels) are filled in
     * automatically by the FuelSalesOrder / FuelSalesOrderItem / FuelPayment
     * model event hooks, exactly like they would be when saved through the UI.
     */
    public function run(): void
    {
        $orders = [
            [
                'date_ordered' => '2026-05-01',
                'sales_order_no' => 'SO-2026-0001',
                'supplier' => 'Petron Depot North',
                'items' => [
                    ['fuel_product' => 'DIESEL', 'unit_price' => 45.50, 'quantity_liters' => 5000],
                    ['fuel_product' => 'GASOLINE', 'unit_price' => 52.00, 'quantity_liters' => 3000],
                ],
                // Comfortably covers the net amount -> should settle as "paid".
                'payments' => [
                    ['payment_date' => '2026-05-10', 'amount' => 250000, 'payment_method' => 'Bank Transfer', 'reference_no' => 'BT-0001'],
                    ['payment_date' => '2026-05-20', 'amount' => 135000, 'payment_method' => 'Cash', 'reference_no' => 'CSH-0002'],
                ],
            ],
            [
                'date_ordered' => '2026-05-15',
                'sales_order_no' => 'SO-2026-0002',
                'supplier' => 'Shell Flying V',
                'items' => [
                    ['fuel_product' => 'DIESEL', 'unit_price' => 46.00, 'quantity_liters' => 4000],
                    ['fuel_product' => 'KEROSENE', 'unit_price' => 48.75, 'quantity_liters' => 1500],
                ],
                // Less than the net amount -> should settle as "partial".
                'payments' => [
                    ['payment_date' => '2026-05-25', 'amount' => 150000, 'payment_method' => 'Check', 'reference_no' => 'CHK-1001'],
                ],
            ],
            [
                'date_ordered' => '2026-06-01',
                'sales_order_no' => 'SO-2026-0003',
                'supplier' => 'Petron Depot South',
                'items' => [
                    ['fuel_product' => 'DIESEL', 'unit_price' => 45.80, 'quantity_liters' => 6000],
                ],
                // No payments -> should settle as "unpaid".
                'payments' => [],
            ],
        ];

        foreach ($orders as $orderData) {
            /** @var FuelSalesOrder $order */
            $order = FuelSalesOrder::query()->firstOrCreate(
                ['sales_order_no' => $orderData['sales_order_no']],
                [
                    'date_ordered' => $orderData['date_ordered'],
                    'supplier' => $orderData['supplier'],
                    'ewt_rate' => 0.005,
                    'vat_divisor' => 1.12,
                ]
            );

            foreach ($orderData['items'] as $itemData) {
                FuelSalesOrderItem::query()->firstOrCreate(
                    [
                        'fuel_sales_order_id' => $order->id,
                        'fuel_product' => $itemData['fuel_product'],
                    ],
                    [
                        'unit_price' => $itemData['unit_price'],
                        'quantity_liters' => $itemData['quantity_liters'],
                    ]
                );
            }

            foreach ($orderData['payments'] as $paymentData) {
                FuelPayment::query()->firstOrCreate(
                    [
                        'fuel_sales_order_id' => $order->id,
                        'reference_no' => $paymentData['reference_no'],
                    ],
                    [
                        'payment_date' => $paymentData['payment_date'],
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                    ]
                );
            }
        }
    }
}
