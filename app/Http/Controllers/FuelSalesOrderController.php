<?php

namespace App\Http\Controllers;

use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Note: totals (total_liters, gross_amount, ewt_amount, net_amount,
 * paid_amount, balance_amount, status) are NOT set here. They are
 * recalculated automatically by FuelSalesOrder::recalculateTotals(), which
 * fires from FuelSalesOrderItem's model events every time an item is
 * saved/deleted. This controller only ever writes date_ordered, supplier,
 * ewt_rate, vat_divisor, remarks, and the item rows.
 */
class FuelSalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = FuelSalesOrder::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('sales_order_no', 'like', "%{$request->search}%")
                      ->orWhere('supplier', 'like', "%{$request->search}%");
                });
            })
            ->latest('date_ordered')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.sales-orders.index', compact('orders'));
    }

    public function create()
    {
        $order = new FuelSalesOrder([
            'sales_order_no' => $this->generateSalesOrderNo(),
            'ewt_rate' => 0.005,
            'vat_divisor' => 1.12,
        ]);

        return view('fuel.sales-orders.create', compact('order'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateOrder($request, null);

        $order = DB::transaction(function () use ($validated) {
            $order = FuelSalesOrder::create([
                'date_ordered' => $validated['date_ordered'],
                'sales_order_no' => $validated['sales_order_no'],
                'supplier' => $validated['supplier'],
                'ewt_rate' => $validated['ewt_rate'],
                'vat_divisor' => $validated['vat_divisor'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $line) {
                $order->items()->create([
                    'fuel_product' => $line['fuel_product'],
                    'unit_price' => $line['unit_price'],
                    'quantity_liters' => $line['quantity_liters'],
                    'remarks' => $line['remarks'] ?? null,
                ]);
            }

            return $order;
        });

        return redirect()->route('fuel-sales-orders.edit', $order)
            ->with('success', "Sales order {$order->sales_order_no} created.");
    }

    public function edit(FuelSalesOrder $fuelSalesOrder)
    {
        $fuelSalesOrder->load(['items', 'payments' => fn ($q) => $q->latest('payment_date')]);

        return view('fuel.sales-orders.edit', ['order' => $fuelSalesOrder]);
    }

    public function update(Request $request, FuelSalesOrder $fuelSalesOrder)
    {
        $validated = $this->validateOrder($request, $fuelSalesOrder->id);

        DB::transaction(function () use ($validated, $fuelSalesOrder) {
            $fuelSalesOrder->update([
                'date_ordered' => $validated['date_ordered'],
                'sales_order_no' => $validated['sales_order_no'],
                'supplier' => $validated['supplier'],
                'ewt_rate' => $validated['ewt_rate'],
                'vat_divisor' => $validated['vat_divisor'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $submittedIds = [];

            foreach ($validated['items'] as $line) {
                if (! empty($line['id'])) {
                    $item = $fuelSalesOrder->items()->findOrFail($line['id']);
                    $item->update([
                        'fuel_product' => $line['fuel_product'],
                        'unit_price' => $line['unit_price'],
                        'quantity_liters' => $line['quantity_liters'],
                        'remarks' => $line['remarks'] ?? null,
                    ]);
                    $submittedIds[] = $item->id;
                } else {
                    $item = $fuelSalesOrder->items()->create([
                        'fuel_product' => $line['fuel_product'],
                        'unit_price' => $line['unit_price'],
                        'quantity_liters' => $line['quantity_liters'],
                        'remarks' => $line['remarks'] ?? null,
                    ]);
                    $submittedIds[] = $item->id;
                }
            }

            // Delete any existing item rows that were removed from the form.
            $fuelSalesOrder->items()->whereNotIn('id', $submittedIds)->delete();
        });

        return back()->with('success', "Sales order {$fuelSalesOrder->sales_order_no} updated.");
    }

    public function destroy(FuelSalesOrder $fuelSalesOrder)
    {
        $fuelSalesOrder->delete();

        return redirect()->route('fuel-sales-orders.index')->with('success', 'Sales order deleted.');
    }

    private function validateOrder(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'date_ordered' => ['required', 'date'],
            'sales_order_no' => [
                'required', 'string', 'max:255',
                'unique:fuel_sales_orders,sales_order_no' . ($ignoreId ? ",{$ignoreId}" : ''),
            ],
            'supplier' => ['required', 'string', 'max:255'],
            'ewt_rate' => ['required', 'numeric', 'min:0'],
            'vat_divisor' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.fuel_product' => ['required', 'string', 'in:DIESEL,REGULAR,PREMIUM'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_liters' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ]);
    }

    private function generateSalesOrderNo(): string
    {
        $prefix = 'SO-'.now()->format('Ymd').'-';

        do {
            $salesOrderNo = $prefix.random_int(10000000, 99999999);
        } while (FuelSalesOrder::withTrashed()->where('sales_order_no', $salesOrderNo)->exists());

        return $salesOrderNo;
    }
}
