<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPayment;
use App\Models\FuelCustomerPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

/**
 * Payments received FROM the customer against a FuelCustomerPurchase.
 * FuelCustomerPurchase::recalculateTotals() and the tracking-number
 * generator both fire automatically from FuelCustomerPayment's model events.
 */
class FuelCustomerPaymentController extends Controller
{
    /**
     * Display customer payment records.
     */
  public function index(Request $request)
    {
        $payments = FuelCustomerPayment::query()
            ->with('customerPurchase')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($q) use ($search) {
                    $q->where(
                        'customer_payment_tracking_no',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'reference_no',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas('customerPurchase', function ($query) use ($search) {
                        $query->where('customer', 'like', "%{$search}%")
                            ->orWhere('sales_order_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('fuel.customer-payments.index', compact('payments'));
    }

    /**
     * Store customer payment with optional proof image.
     *
     * Local Laragon: payment_proofs = local
     * Production EC2: payment_proofs = s3
     */
    public function store(
        Request $request,
        FuelCustomerPurchase $fuelCustomerPurchase
    ) {
        $validated = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'proof_image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max('5mb'),
            ],
        ], [
            'proof_image.image' => 'Only image files are allowed.',
            'proof_image.max' => 'The payment proof must not exceed 5 MB.',
        ]);

        unset($validated['proof_image']);

        $uploadedPath = null;
        $disk = Storage::disk('payment_proofs');

        try {
            /*
             * Lock the customer purchase row while validating
             * its status and recording a payment.
             */
            DB::transaction(function () use (
                $request,
                $fuelCustomerPurchase,
                $validated,
                $disk,
                &$uploadedPath
            ) {
                $purchase = FuelCustomerPurchase::query()
                    ->whereKey($fuelCustomerPurchase->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $status = strtolower(trim((string) $purchase->status));

                if (in_array($status, ['paid', 'overpaid'], true)) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'This purchase is already paid or overpaid. Additional payments are not permitted.',
                    ]);
                }

                /*
                 * Prevent payments exceeding the outstanding
                 * balance, using existing payment records.
                 */
                $totalPayables = round(
                    (float) $purchase->total_payables,
                    2
                );

                $totalPaid = round(
                    (float) $purchase->payments()->sum('amount'),
                    2
                );

                $balance = round(
                    $totalPayables - $totalPaid,
                    2
                );

                $amount = round(
                    (float) $validated['amount'],
                    2
                );

                if ($balance <= 0 || $amount > $balance) {
                    throw ValidationException::withMessages([
                        'amount' =>
                            'Payment exceeds the remaining balance of ₱' .
                            number_format(max(0, $balance), 2) . '.',
                    ]);
                }

                /*
                 * Upload optional image attachment.
                 */
                $paymentData = $validated;

                if ($request->hasFile('proof_image')) {
                    $uploadedPath = $request
                        ->file('proof_image')
                        ->store(
                            'customer-payment-proofs',
                            'payment_proofs'
                        );

                    if (!$uploadedPath) {
                        throw new \RuntimeException(
                            'Payment proof upload returned no file path.'
                        );
                    }

                    $paymentData['proof_image'] = $uploadedPath;
                }

                /*
                 * Existing FuelCustomerPayment model events
                 * continue generating tracking numbers and
                 * recalculating customer purchase totals.
                 */
                $purchase->payments()->create($paymentData);
            });

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            Log::error('Customer payment recording failed', [
                'purchase_id' => $fuelCustomerPurchase->getKey(),
                'storage_driver' => config(
                    'filesystems.disks.payment_proofs.driver'
                ),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            /*
             * Remove newly uploaded evidence only when
             * the payment transaction failed.
             */
            if ($uploadedPath) {
                try {
                    $disk->delete($uploadedPath);
                } catch (\Throwable $cleanupException) {
                    Log::error('Customer payment proof cleanup failed', [
                        'path' => $uploadedPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            return back()
                ->withErrors([
                    'payment' =>
                        'Unable to record customer payment. Please contact the administrator.',
                ])
                ->withInput($request->except('proof_image'));
        }

    return redirect()
    ->route('fuel-customer-purchases.index')
    ->with('success', 'Customer payment recorded successfully.');
    }

    /**
     * Display saved proof image.
     *
     * Compatible with local storage and private AWS S3.
     */
    public function showProof(
        FuelCustomerPayment $fuelCustomerPayment
    ) {
        // Add your payment-specific authorization policy here.
        // The existing route must retain its authentication middleware.

        $imagePath = $fuelCustomerPayment->proof_image;

        abort_if(
            !$imagePath,
            404,
            'No payment proof attached.'
        );

        $disk = Storage::disk('payment_proofs');

        abort_unless(
            $disk->exists($imagePath),
            404,
            'Payment proof image not found.'
        );

        $mimeType = $disk->mimeType($imagePath);

        abort_unless(
            in_array($mimeType, [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ], true),
            404,
            'Invalid payment proof format.'
        );

        return $disk->response(
            $imagePath,
            null,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    /**
     * Delete customer payment.
     *
     * Retain payment proof for auditing and restoration
     * when FuelCustomerPayment uses SoftDeletes.
     */
    public function destroy(
        FuelCustomerPayment $fuelCustomerPayment
    ) {
        $fuelCustomerPayment->delete();

        return back()->with(
            'success',
            'Customer payment removed successfully.'
        );
    }
}