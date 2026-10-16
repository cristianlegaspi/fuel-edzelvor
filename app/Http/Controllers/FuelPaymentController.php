<?php

namespace App\Http\Controllers;

use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
/**
 * Payments made TO suppliers against FuelSalesOrder.
 *
 * FuelSalesOrder::recalculateTotals() is handled automatically
 * through FuelPayment model events.
 */
class FuelPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = FuelPayment::query()
            ->with('salesOrder')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', function ($orderQuery) use ($search) {
                            $orderQuery->where('sales_order_no', 'like', "%{$search}%")
                                ->orWhere('supplier', 'like', "%{$search}%");
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
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'proof_image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max('5mb')],
            'payment_order_id' => ['nullable', 'integer'],
        ], [
            'proof_image.required' => 'Receipt is required. Please attach proof of payment before saving.',
            'proof_image.image' => 'Only valid image receipts are allowed.',
            'proof_image.mimes' => 'Only JPG, PNG, WEBP, and GIF image receipts are accepted.',
            'proof_image.max' => 'The receipt must not exceed 5 MB.',
        ]);

        unset($validated['proof_image'], $validated['payment_order_id']);
        $uploadedPath = null;

        try {
            DB::transaction(function () use ($request, $fuelSalesOrder, $validated, &$uploadedPath) {
                $order = FuelSalesOrder::query()->whereKey($fuelSalesOrder->getKey())->lockForUpdate()->firstOrFail();
                $status = strtolower(trim((string) $order->status));
                if (in_array($status, ['paid', 'overpaid'], true)) {
                    throw ValidationException::withMessages([
                        'payment' => 'This supplier order is paid or overpaid. Further payments are disabled.',
                    ]);
                }

                // Lock the order row to serialize new payments for the same order.
                $netCents = (int) round((float) $order->net_amount * 100);
                $paidCents = (int) round((float) $order->payments()->sum('amount') * 100);
                $amountCents = (int) round((float) $validated['amount'] * 100);
                $balanceCents = $netCents - $paidCents;
                if ($balanceCents <= 0 || $amountCents > $balanceCents) {
                    throw ValidationException::withMessages([
                        'amount' => 'Payment exceeds the remaining balance of ₱' . number_format(max(0, $balanceCents) / 100, 2) . '.',
                    ]);
                }

                $data = $validated;
                if ($request->hasFile('proof_image')) {
                    $uploadedPath = $request->file('proof_image')->store('supplier-payment-proofs', 'payment_proofs');
                    if (!$uploadedPath) throw new \RuntimeException('Payment proof upload returned an empty path.');
                    $data['proof_image'] = $uploadedPath;
                }

                // Existing FuelPayment model events handle totals/status recalculation.
                $order->payments()->create($data);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Supplier payment save failed', [
                'order_id' => $fuelSalesOrder->getKey(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            if ($uploadedPath) {
                try {
                    Storage::disk('payment_proofs')->delete($uploadedPath);
                } catch (\Throwable $cleanupError) {
                    Log::error('Supplier payment proof cleanup failed', [
                        'path' => $uploadedPath, 'message' => $cleanupError->getMessage(),
                    ]);
                }
            }
            return back()->withErrors(['payment' => 'Unable to record supplier payment. Contact the administrator.'])
                ->withInput($request->except('proof_image'));
        }

        return redirect()->route('fuel-sales-orders.index')
            ->with('success', 'Supplier payment recorded successfully.');
    }

    public function showProof(FuelPayment $fuelPayment)
    {
        // Apply record-level authorization policies as appropriate for your roles.
        $path = $fuelPayment->proof_image;
        abort_if(!$path, 404, 'No proof attached.');
        $disk = Storage::disk('payment_proofs');

        // Legacy receipts may still be held on the server's private local disk.
        if (!$disk->exists($path)) {
            $disk = Storage::disk('local');
        }
        abort_unless($disk->exists($path), 404, 'Proof image not found.');
        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function destroy(FuelPayment $fuelPayment)
    {
        // Payment evidence is deliberately retained for audit/restore.
        $fuelPayment->delete();
        return back()->with('success', 'Supplier payment removed successfully.');
    }
}

