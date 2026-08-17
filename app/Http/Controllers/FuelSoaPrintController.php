<?php

namespace App\Http\Controllers;

use App\Models\FuelSalesOrder;

class FuelSoaPrintController extends Controller
{
    public function __invoke(FuelSalesOrder $salesOrder)
    {
        $salesOrder->load([
            'items' => function ($query) {
                $query->orderBy('id');
            },
            'payments' => function ($query) {
                $query->orderBy('payment_date');
            },
        ]);

        $items = $salesOrder->items;
        $payments = $salesOrder->payments;

        $totalPayables = (float) $salesOrder->net_amount;
        $totalPaid = (float) $salesOrder->paid_amount;

        // Your balance_amount is negative when payable,
        // so this shows the remaining balance as positive.
        $remainingBalance = abs((float) $salesOrder->balance_amount);

        return view('fuel-payments.print-soa', [
            'salesOrder' => $salesOrder,
            'items' => $items,
            'payments' => $payments,
            'totalPayables' => $totalPayables,
            'totalPaid' => $totalPaid,
            'remainingBalance' => $remainingBalance,
        ]);
    }
}