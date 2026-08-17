<?php

namespace App\Http\Controllers;

use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;

/**
 * Payments made TO the supplier against a FuelSalesOrder.
 * FuelSalesOrder::recalculateTotals() fires automatically from
 * FuelPayment's model events, so paid_amount/balance_amount/status
 * update themselves — this controller just writes the payment row.
 */
class FuelPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = FuelPayment::query()
            ->with('salesOrder')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('reference_no', 'like', "%{$request->search}%")
                        ->orWhereHas('salesOrder', function ($q) use ($request) {
                            $q->where('sales_order_no', 'like', "%{$request->search}%")
                                ->orWhere('supplier', 'like', "%{$request->search}%");
                        });
                });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.supplier-payments.index', compact('payments'));
    }

    public function store(Request $request, FuelSalesOrder $fuelSalesOrder)
    {
        $validated = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $fuelSalesOrder->payments()->create($validated);

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(FuelPayment $fuelPayment)
    {
        $fuelPayment->delete();

        return back()->with('success', 'Payment removed.');
    }
}
