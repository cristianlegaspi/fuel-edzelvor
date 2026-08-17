<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPurchase;
use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All money totals here (total_liters, total_payables, net_income,
 * balance_short_over, status, ...) are computed automatically by
 * FuelCustomerPurchase::recalculateTotals(), which fires from the item
 * model events. This controller never writes those fields directly.
 *
 * Stock validation (not enough remaining liters for a fuel product) is
 * enforced inside FuelCustomerPurchaseItem's saving() hook and throws a
 * ValidationException — we catch it here and turn it back into normal
 * form errors instead of a 500.
 */
class FuelCustomerPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = FuelCustomerPurchase::query()
            ->with('salesOrder')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('customer', 'like', "%{$request->search}%")
                      ->orWhere('sales_order_no', 'like', "%{$request->search}%");
                });
            })
            ->latest('date_ordered')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.customer-purchases.index', compact('purchases'));
    }

    public function create()
    {
        $purchase = new FuelCustomerPurchase();
        $salesOrders = $this->salesOrderOptions();

        return view('fuel.customer-purchases.create', compact('purchase', 'salesOrders'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePurchase($request);

        try {
            $purchase = DB::transaction(function () use ($validated) {
                $order = FuelSalesOrder::findOrFail($validated['fuel_sales_order_id']);

                $purchase = FuelCustomerPurchase::create([
                    'fuel_sales_order_id' => $order->id,
                    'date_ordered' => $order->date_ordered,
                    'sales_order_no' => $order->sales_order_no,
                    'supplier' => $order->supplier,
                    'customer' => $validated['customer'],
                    'tanker_details' => $validated['tanker_details'] ?? null,
                    'order_no_details' => $validated['order_no_details'] ?? null,
                    'atl_date' => $validated['atl_date'] ?? null,
                    'atl_no' => $validated['atl_no'] ?? null,
                    'garage' => $validated['garage'] ?? 0,
                    'agent_comm' => $validated['agent_comm'] ?? 0,
                    'receiver' => $validated['receiver'] ?? 0,
                    'others_amount' => $validated['others_amount'] ?? 0,
                    'others_comment' => $validated['others_comment'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                foreach ($validated['items'] as $line) {
                    $purchase->items()->create([
                        'fuel_product' => $line['fuel_product'],
                        'liters' => $line['liters'],
                        'freight_alwin' => $line['freight_alwin'] ?? 0,
                        'freight_tanker' => $line['freight_tanker'] ?? 0,
                        'freight_040' => $line['freight_040'] ?? 0.400,
                        'amount_per_liter' => $line['amount_per_liter'],
                        'selling_price' => $line['selling_price'],
                        'ewt_rate' => $line['ewt_rate'] ?? 0.01,
                        'remarks' => $line['remarks'] ?? null,
                    ]);
                }

                return $purchase;
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('fuel-customer-purchases.edit', $purchase)
            ->with('success', 'Customer purchase created.');
    }

    public function edit(FuelCustomerPurchase $fuelCustomerPurchase)
    {
        $fuelCustomerPurchase->load([
            'items',
            'payments' => fn ($q) => $q->latest('payment_date'),
            'tankerRecord',
            'salesOrder.items',
        ]);

        $salesOrders = $this->salesOrderOptions();

        return view('fuel.customer-purchases.edit', [
            'purchase' => $fuelCustomerPurchase,
            'salesOrders' => $salesOrders,
        ]);
    }

    public function update(Request $request, FuelCustomerPurchase $fuelCustomerPurchase)
    {
        $validated = $this->validatePurchase($request);

        try {
            DB::transaction(function () use ($validated, $fuelCustomerPurchase) {
                $order = FuelSalesOrder::findOrFail($validated['fuel_sales_order_id']);

                $fuelCustomerPurchase->update([
                    'fuel_sales_order_id' => $order->id,
                    'date_ordered' => $order->date_ordered,
                    'sales_order_no' => $order->sales_order_no,
                    'supplier' => $order->supplier,
                    'customer' => $validated['customer'],
                    'tanker_details' => $validated['tanker_details'] ?? null,
                    'order_no_details' => $validated['order_no_details'] ?? null,
                    'atl_date' => $validated['atl_date'] ?? null,
                    'atl_no' => $validated['atl_no'] ?? null,
                    'garage' => $validated['garage'] ?? 0,
                    'agent_comm' => $validated['agent_comm'] ?? 0,
                    'receiver' => $validated['receiver'] ?? 0,
                    'others_amount' => $validated['others_amount'] ?? 0,
                    'others_comment' => $validated['others_comment'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                $submittedIds = [];

                foreach ($validated['items'] as $line) {
                    $payload = [
                        'fuel_product' => $line['fuel_product'],
                        'liters' => $line['liters'],
                        'freight_alwin' => $line['freight_alwin'] ?? 0,
                        'freight_tanker' => $line['freight_tanker'] ?? 0,
                        'freight_040' => $line['freight_040'] ?? 0.400,
                        'amount_per_liter' => $line['amount_per_liter'],
                        'selling_price' => $line['selling_price'],
                        'ewt_rate' => $line['ewt_rate'] ?? 0.01,
                        'remarks' => $line['remarks'] ?? null,
                    ];

                    if (! empty($line['id'])) {
                        $item = $fuelCustomerPurchase->items()->findOrFail($line['id']);
                        $item->update($payload);
                        $submittedIds[] = $item->id;
                    } else {
                        $item = $fuelCustomerPurchase->items()->create($payload);
                        $submittedIds[] = $item->id;
                    }
                }

                $fuelCustomerPurchase->items()->whereNotIn('id', $submittedIds)->delete();
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Customer purchase updated.');
    }

    public function destroy(FuelCustomerPurchase $fuelCustomerPurchase)
    {
        $fuelCustomerPurchase->delete();

        return redirect()->route('fuel-customer-purchases.index')->with('success', 'Customer purchase deleted.');
    }

    private function validatePurchase(Request $request): array
    {
        return $request->validate([
            'fuel_sales_order_id' => ['required', 'exists:fuel_sales_orders,id'],
            'customer' => ['required', 'string', 'max:255'],
            'tanker_details' => ['nullable', 'string', 'max:255'],
            'order_no_details' => ['nullable', 'string', 'max:255'],
            'atl_date' => ['nullable', 'date'],
            'atl_no' => ['nullable', 'string', 'max:255'],
            'garage' => ['nullable', 'numeric'],
            'agent_comm' => ['nullable', 'numeric'],
            'receiver' => ['nullable', 'numeric'],
            'others_amount' => ['nullable', 'numeric'],
            'others_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.fuel_product' => ['required', 'string', 'in:DIESEL,REGULAR,PREMIUM'],
            'items.*.liters' => ['required', 'numeric', 'min:0.01'],
            'items.*.freight_alwin' => ['nullable', 'numeric'],
            'items.*.freight_tanker' => ['nullable', 'numeric'],
            'items.*.freight_040' => ['nullable', 'numeric'],
            'items.*.amount_per_liter' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['required', 'numeric', 'min:0'],
            'items.*.ewt_rate' => ['nullable', 'numeric'],
            'items.*.remarks' => ['nullable', 'string'],
        ]);
    }

    /** Sales orders for the dropdown, each labeled with remaining stock like the Filament version. */
    private function salesOrderOptions()
    {
        return FuelSalesOrder::query()
            ->with('items')
            ->orderByDesc('date_ordered')
            ->get()
            ->map(function (FuelSalesOrder $order) {
                $remaining = (float) $order->items->sum('remaining_liters');
                $order->stock_label = $remaining > 0
                    ? number_format($remaining, 2).' L available'
                    : 'NO STOCK';

                return $order;
            });
    }
}
