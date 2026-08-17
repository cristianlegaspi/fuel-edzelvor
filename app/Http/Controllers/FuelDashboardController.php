<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPurchase;
use App\Models\FuelCustomerPurchaseItem;
use App\Models\FuelSalesOrder;
use App\Models\FuelSalesOrderItem;

class FuelDashboardController extends Controller
{
    public function index()
    {
        $salesOrders = FuelSalesOrder::query();
        $customerPurchases = FuelCustomerPurchase::query();

        $stats = [
            'totalSalesOrders' => (clone $salesOrders)->count(),
            'unpaidSalesOrders' => (clone $salesOrders)->where('status', 'unpaid')->count(),
            'totalSupplierBalance' => (clone $salesOrders)->sum('balance_amount') * -1,

            'totalCustomerPurchases' => (clone $customerPurchases)->count(),
            'unpaidCustomerPurchases' => (clone $customerPurchases)->where('status', 'unpaid')->count(),
            'totalNetIncome' => (clone $customerPurchases)->sum('net_income'),
            'totalOutstandingReceivable' => (clone $customerPurchases)->where('balance_short_over', '<', 0)->sum('balance_short_over') * -1,
        ];

        // Customer summary card figures
        $totalPayables = (float) (clone $customerPurchases)->sum('total_payables');
        $totalPaid = (float) (clone $customerPurchases)->sum('payment_amount');

        $customerSummary = [
            'totalLiters' => (float) (clone $customerPurchases)->sum('total_liters'),
            'totalPayables' => $totalPayables,
            'totalPaid' => $totalPaid,
            'remainingBalance' => round($totalPayables - $totalPaid, 2),
            'netIncome' => (float) (clone $customerPurchases)->sum('net_income'),
        ];

        // Collection rate — how much of what's payable has actually been collected
        $customerSummary['collectionRate'] = $totalPayables > 0
            ? round(min(100, ($totalPaid / $totalPayables) * 100), 1)
            : 0;

        $recentSalesOrders = FuelSalesOrder::latest('date_ordered')->take(6)->get();
        $recentPurchases = FuelCustomerPurchase::with('salesOrder')->latest('date_ordered')->take(6)->get();

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $rangeStart = $months->first();

        // Combo chart: monthly net income (line) + monthly liters sold (bar), last 6 months
        $purchaseRows = (clone $customerPurchases)
            ->where('date_ordered', '>=', $rangeStart)
            ->get(['date_ordered', 'net_income', 'total_liters']);

        $netIncomeByMonth = $purchaseRows
            ->groupBy(fn ($row) => $row->date_ordered->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('net_income'));

        $litersByMonth = $purchaseRows
            ->groupBy(fn ($row) => $row->date_ordered->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('total_liters'));

        $performanceTrend = [
            'labels' => $months->map(fn ($m) => $m->format('M Y'))->all(),
            'netIncome' => $months->map(fn ($m) => (float) ($netIncomeByMonth[$m->format('Y-m')] ?? 0))->all(),
            'liters' => $months->map(fn ($m) => (float) ($litersByMonth[$m->format('Y-m')] ?? 0))->all(),
        ];

        // This-month vs last-month net income, for the KPI trend card
        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $netIncomeThisMonth = (float) (clone $customerPurchases)
            ->where('date_ordered', '>=', $thisMonthStart)
            ->sum('net_income');

        $netIncomeLastMonth = (float) (clone $customerPurchases)
            ->whereBetween('date_ordered', [$lastMonthStart, $lastMonthEnd])
            ->sum('net_income');

        $netIncomeChangePct = $netIncomeLastMonth != 0
            ? round((($netIncomeThisMonth - $netIncomeLastMonth) / abs($netIncomeLastMonth)) * 100, 1)
            : null;

        $netIncomeKpi = [
            'currentLabel' => now()->format('F Y'),
            'current' => $netIncomeThisMonth,
            'previousLabel' => $lastMonthStart->format('F Y'),
            'previous' => $netIncomeLastMonth,
            'changePct' => $netIncomeChangePct,
        ];

        // Stacked bar: customer liters sold by fuel product, last 4 months
        $productMonths = collect(range(3, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $productRangeStart = $productMonths->first();

        $productRows = FuelCustomerPurchaseItem::query()
            ->whereNull('deleted_at')
            ->whereHas('customerPurchase', function ($query) use ($productRangeStart) {
                $query->whereNull('deleted_at')->where('date_ordered', '>=', $productRangeStart);
            })
            ->with(['customerPurchase:id,date_ordered'])
            ->get(['fuel_customer_purchase_id', 'fuel_product', 'liters']);

        $products = $productRows->pluck('fuel_product')->unique()->sort()->values();

        $volumeByProduct = [
            'labels' => $productMonths->map(fn ($m) => $m->format('M Y'))->all(),
            'products' => $products->all(),
            'series' => $products->map(function ($product) use ($productRows, $productMonths) {
                return $productMonths->map(function ($m) use ($productRows, $product) {
                    return (float) $productRows
                        ->where('fuel_product', $product)
                        ->filter(fn ($row) => $row->customerPurchase && $row->customerPurchase->date_ordered->format('Y-m') === $m->format('Y-m'))
                        ->sum('liters');
                })->all();
            })->all(),
        ];

        // Doughnut chart: remaining stock by fuel product
        $stockByProduct = FuelSalesOrderItem::query()
            ->whereNull('deleted_at')
            ->selectRaw('fuel_product, SUM(remaining_liters) as total')
            ->groupBy('fuel_product')
            ->having('total', '>', 0)
            ->pluck('total', 'fuel_product');

        $stocksRemaining = [
            'labels' => $stockByProduct->keys()->all(),
            'values' => $stockByProduct->values()->map(fn ($v) => (float) $v)->all(),
            'total' => (float) $stockByProduct->sum(),
        ];

        return view('fuel.dashboard', compact(
            'stats',
            'customerSummary',
            'recentSalesOrders',
            'recentPurchases',
            'performanceTrend',
            'netIncomeKpi',
            'volumeByProduct',
            'stocksRemaining'
        ));
    }
}
