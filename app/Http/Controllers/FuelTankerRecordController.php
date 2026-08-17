<?php

namespace App\Http\Controllers;

use App\Models\FuelTankerRecord;
use Illuminate\Http\Request;

/**
 * Tanker records are auto-created/deleted by FuelCustomerPurchase whenever
 * tanker_details is filled in/cleared, so there's no create/destroy route
 * here — only listing them and editing the driver/expense fields on the
 * auto-created row. net_income recalculates itself via the model's
 * saving() hook.
 */
class FuelTankerRecordController extends Controller
{
    public function index(Request $request)
    {
        $records = FuelTankerRecord::query()
            ->with('customerPurchase.items')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('driver_name', 'like', "%{$request->search}%")
                    ->orWhereHas('customerPurchase', function ($q) use ($request) {
                        $q->where('tanker_details', 'like', "%{$request->search}%")
                            ->orWhere('customer', 'like', "%{$request->search}%");
                    });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.tanker-records.index', compact('records'));
    }

    public function edit(FuelTankerRecord $fuelTankerRecord)
    {
        $fuelTankerRecord->load('customerPurchase.items');

        return view('fuel.tanker-records.edit', ['record' => $fuelTankerRecord]);
    }

    public function update(Request $request, FuelTankerRecord $fuelTankerRecord)
    {
        $validated = $request->validate([
            'date_delivered' => ['nullable', 'date'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_salary' => ['nullable', 'numeric'],
            'date_paid_driver' => ['nullable', 'date'],
            'other_expenses' => ['nullable', 'numeric'],
            'other_expenses_details' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $fuelTankerRecord->update($validated);

        return back()->with('success', 'Tanker record updated.');
    }
}
