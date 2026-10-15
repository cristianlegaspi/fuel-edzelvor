<?php

namespace App\Http\Controllers;

use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

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

            'proof_image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max('5mb'),
            ],
        ], [
            'proof_image.image' => 'Only image attachments are allowed.',
            'proof_image.max' => 'The image must not exceed 5 MB.',
        ]);

        unset($validated['proof_image']);

        $imagePath = null;

        if ($request->hasFile('proof_image')) {
            $imagePath = $request->file('proof_image')
                ->store('supplier-payment-proofs', 'local');

            $validated['proof_image'] = $imagePath;
        }

        try {
            $fuelSalesOrder->payments()->create($validated);
        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('local')->delete($imagePath);
            }

            throw $e;
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function showProof(FuelPayment $fuelPayment)
    {
        if (
            !$fuelPayment->proof_image ||
            !Storage::disk('local')->exists($fuelPayment->proof_image)
        ) {
            abort(404, 'Payment proof not found.');
        }

        $path = Storage::disk('local')->path($fuelPayment->proof_image);

        $mimeType = Storage::disk('local')
            ->mimeType($fuelPayment->proof_image);

        if (!in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ], true)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function destroy(FuelPayment $fuelPayment)
    {
        $imagePath = $fuelPayment->proof_image;

        $fuelPayment->delete();

        if ($imagePath) {
            Storage::disk('local')->delete($imagePath);
        }

        return back()->with('success', 'Payment removed.');
    }
}
