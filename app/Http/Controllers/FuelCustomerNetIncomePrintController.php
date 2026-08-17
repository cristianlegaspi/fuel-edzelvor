<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FuelCustomerNetIncomePrintController extends Controller
{
    /**
     * Interactive report page (year + customer filters, no print chrome).
     */
    public function index(Request $request)
    {
        $data = $this->buildReport($request);

        $customers = FuelCustomerPurchase::query()
            ->select('customer')
            ->distinct()
            ->whereNotNull('customer')
            ->orderBy('customer')
            ->pluck('customer');

        return view('fuel.customer-net-income.index', $data + [
            'customers' => $customers,
        ]);
    }

    /**
     * Print-friendly version of the same report.
     */
    public function summary(Request $request)
    {
        return view('fuel.customer-net-income.print-summary', $this->buildReport($request));
    }

    private function buildReport(Request $request): array
    {
        $year = (int) ($request->query('year') ?: now()->year);
        $selectedCustomer = trim((string) $request->query('customer', 'all'));

        $query = FuelCustomerPurchase::query()
            ->with(['items', 'payments', 'salesOrder'])
            ->whereYear('date_ordered', $year);

        if ($selectedCustomer !== '' && strtolower($selectedCustomer) !== 'all') {
            $query->where('customer', $selectedCustomer);
        }

        $purchases = $query
            ->orderBy('customer')
            ->orderBy('date_ordered')
            ->get();

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $summaryRows = $purchases
            ->groupBy(fn (FuelCustomerPurchase $purchase): string => trim((string) $purchase->customer) ?: 'NO CUSTOMER')
            ->map(function ($customerPurchases, string $customer) use ($months): array {
                $monthly = [];

                foreach ($months as $monthNo => $monthName) {
                    $monthPurchases = $customerPurchases->filter(function (FuelCustomerPurchase $purchase) use ($monthNo): bool {
                        if (! $purchase->date_ordered) {
                            return false;
                        }

                        return Carbon::parse($purchase->date_ordered)->month === $monthNo;
                    });

                    $monthly[$monthNo] = [
                        'name' => $monthName,
                        'net_income' => (float) $monthPurchases->sum('net_income'),
                        'balance' => (float) $monthPurchases->sum('balance_short_over'),
                    ];
                }

                return [
                    'customer' => $customer,
                    'total_net_income' => (float) $customerPurchases->sum('net_income'),
                    'total_balance' => (float) $customerPurchases->sum('balance_short_over'),
                    'monthly' => $monthly,
                ];
            })
            ->values();

        $grandMonthly = [];

        foreach ($months as $monthNo => $monthName) {
            $grandMonthly[$monthNo] = [
                'name' => $monthName,
                'net_income' => (float) $summaryRows->sum(fn ($row) => $row['monthly'][$monthNo]['net_income'] ?? 0),
                'balance' => (float) $summaryRows->sum(fn ($row) => $row['monthly'][$monthNo]['balance'] ?? 0),
            ];
        }

        $grandTotal = [
            'net_income' => (float) $summaryRows->sum('total_net_income'),
            'balance' => (float) $summaryRows->sum('total_balance'),
        ];

        return [
            'year' => $year,
            'selectedCustomer' => $selectedCustomer,
            'months' => $months,
            'summaryRows' => $summaryRows,
            'grandMonthly' => $grandMonthly,
            'grandTotal' => $grandTotal,
        ];
    }
}
