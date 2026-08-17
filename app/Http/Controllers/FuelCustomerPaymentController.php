<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPayment;
use App\Models\FuelCustomerPurchase;
use Illuminate\Http\Request;

/**
 * Payments received FROM the customer against a FuelCustomerPurchase.
 * FuelCustomerPurchase::recalculateTotals() and the tracking-number
 * generator both fire automatically from FuelCustomerPayment's model events.
 */
class FuelCustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = FuelCustomerPayment::query()
            ->with('customerPurchase')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('customer_payment_tracking_no', 'like', "%{$request->search}%")
                        ->orWhere('reference_no', 'like', "%{$request->search}%")
                        ->orWhereHas('customerPurchase', function ($q) use ($request) {
                            $q->where('customer', 'like', "%{$request->search}%")
                                ->orWhere('sales_order_no', 'like', "%{$request->search}%");
                        });
                });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.customer-payments.index', compact('payments'));
    }

    public function store(Request $request, FuelCustomerPurchase $fuelCustomerPurchase)
    {
        $validated = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $fuelCustomerPurchase->payments()->create($validated);

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(FuelCustomerPayment $fuelCustomerPayment)
    {
        $fuelCustomerPayment->delete();

        return back()->with('success', 'Payment removed.');
    }
}
