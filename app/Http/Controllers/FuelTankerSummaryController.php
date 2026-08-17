<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPurchase;
use App\Models\FuelTankerRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FuelTankerSummaryController extends Controller
{
    public function print(Request $request): View
    {
        $selectedTanker = trim((string) $request->query('tanker', 'all'));

        $availableTankers = FuelCustomerPurchase::query()
            ->whereNotNull('tanker_details')
            ->where('tanker_details', '!=', '')
            ->whereNull('deleted_at')
            ->select('tanker_details')
            ->distinct()
            ->orderBy('tanker_details')
            ->pluck('tanker_details', 'tanker_details')
            ->all();

        $records = FuelTankerRecord::query()
            ->with([
                'customerPurchase.items',
            ])
            ->whereHas('customerPurchase', function ($query) use ($selectedTanker): void {
                $query
                    ->whereNotNull('tanker_details')
                    ->where('tanker_details', '!=', '');

                if ($selectedTanker !== '' && $selectedTanker !== 'all') {
                    $query->where('tanker_details', $selectedTanker);
                }
            })
            ->get()
            ->sortBy([
                fn (FuelTankerRecord $record) =>
                    $record->customerPurchase?->tanker_details ?? '',

                fn (FuelTankerRecord $record) =>
                    $record->customerPurchase?->date_ordered?->format('Y-m-d') ?? '',
            ])
            ->values();

        $groupedRecords = $records->groupBy(
            fn (FuelTankerRecord $record): string =>
                $record->customerPurchase?->tanker_details
                    ?: 'Unassigned Tanker'
        );

        $grandTotals = [
            'liters' => round(
                $records->sum(
                    fn (FuelTankerRecord $record): float =>
                        (float) ($record->customerPurchase?->total_liters ?? 0)
                ),
                2
            ),

            'income' => round(
                $records->sum(
                    fn (FuelTankerRecord $record): float =>
                        $record->getFreightIncome()
                ),
                2
            ),

            'driver_salary' => round(
                $records->sum(
                    fn (FuelTankerRecord $record): float =>
                        (float) ($record->driver_salary ?? 0)
                ),
                2
            ),

            'other_expenses' => round(
                $records->sum(
                    fn (FuelTankerRecord $record): float =>
                        (float) ($record->other_expenses ?? 0)
                ),
                2
            ),

            'net_income' => round(
                $records->sum(
                    fn (FuelTankerRecord $record): float =>
                        (float) ($record->net_income ?? 0)
                ),
                2
            ),
        ];

        return view('fuel.tanker.print-summary', [
            'records' => $records,
            'groupedRecords' => $groupedRecords,
            'availableTankers' => $availableTankers,
            'selectedTanker' => $selectedTanker,
            'grandTotals' => $grandTotals,
        ]);
    }
}