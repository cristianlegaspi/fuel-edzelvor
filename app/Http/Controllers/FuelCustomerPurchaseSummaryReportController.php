<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPurchase;
use Illuminate\Http\Request;

class FuelCustomerPurchaseSummaryReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $selectedCustomer = trim((string) $request->query('customer'));

        if ($selectedCustomer === '') {
            abort(404, 'No customer selected.');
        }

        $purchases = FuelCustomerPurchase::query()
            ->with(['items', 'payments', 'salesOrder'])
            ->where('customer', $selectedCustomer)
            ->orderBy('date_ordered')
            ->orderBy('sales_order_no')
            ->get();

        $totalOrders = $purchases->count();

        $totalLiters = $purchases->sum(function (FuelCustomerPurchase $purchase): float {
            return (float) ($purchase->total_liters ?? $purchase->items->sum('liters'));
        });

        $totalPayables = $purchases->sum(function (FuelCustomerPurchase $purchase): float {
            return (float) ($purchase->total_payables ?? $purchase->items->sum('payables'));
        });

        $totalPaid = $purchases->sum(function (FuelCustomerPurchase $purchase): float {
            return (float) $purchase->payments->sum('amount');
        });

        $remainingBalance = max($totalPayables - $totalPaid, 0);

        return view('fuel.customer-purchases.print-summary-report', [
            'selectedCustomer' => $selectedCustomer,
            'purchases' => $purchases,
            'summary' => [
                'total_orders' => $totalOrders,
                'total_liters' => $totalLiters,
                'total_payables' => $totalPayables,
                'total_paid' => $totalPaid,
                'remaining_balance' => $remainingBalance,
            ],
        ]);
    }
}