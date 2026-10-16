<?php

namespace App\Http\Controllers;

use App\Models\FuelCustomerPayment;
use App\Models\FuelCustomerPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Log;


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
                    ->orWhereHas(
                        'customerPurchase',
                        function ($query) use ($search) {
                            $query->where('customer', 'like', "%{$search}%")
                                ->orWhere(
                                    'sales_order_no',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view(
            'fuel.customer-payments.index',
            compact('payments')
        );
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
            'payment_date' => [
                'nullable',
                'date',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reference_no' => [
                'nullable',
                'string',
                'max:255',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
            'proof_image' => [
                'nullable',
                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                        'gif',
                    ])
                    ->max('5mb'),
            ],
        ]);

        unset($validated['proof_image']);

        $uploadedPath = null;
        $disk = Storage::disk('payment_proofs');

        /*
         * STEP 1: Upload payment proof.
         */
        if ($request->hasFile('proof_image')) {
            try {
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

                $validated['proof_image'] = $uploadedPath;

            } catch (\Throwable $e) {

                Log::error('Customer payment proof upload failed', [
                    'purchase_id' => $fuelCustomerPurchase->id,
                    'disk' => config(
                        'filesystems.disks.payment_proofs.driver'
                    ),
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->withErrors([
                        'proof_image' =>
                            'Unable to upload payment proof. Please contact the administrator.',
                    ])
                    ->withInput(
                        $request->except('proof_image')
                    );
            }
        }

        /*
         * STEP 2: Save customer payment.
         *
         * Existing model events handle:
         * - Customer payment tracking number
         * - Balance recalculation
         * - Payment status updates
         */
        try {

            $fuelCustomerPurchase->payments()->create(
                $validated
            );

        } catch (\Throwable $e) {

            Log::error('Customer payment database save failed', [
                'purchase_id' => $fuelCustomerPurchase->id,
                'image_path' => $uploadedPath,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            /*
             * Remove the newly uploaded image
             * when database saving fails.
             */
            if ($uploadedPath) {
                try {
                    $disk->delete($uploadedPath);
                } catch (\Throwable $cleanupException) {
                    Log::error('Customer proof cleanup failed', [
                        'path' => $uploadedPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            return back()
                ->withErrors([
                    'payment' =>
                        'Unable to save customer payment. Please contact the administrator.',
                ])
                ->withInput(
                    $request->except('proof_image')
                );
        }

        return back()->with(
            'success',
            'Customer payment recorded successfully.'
        );
    }

    /**
     * Display saved proof of payment.
     *
     * Supports Laravel local storage and AWS S3.
     */
    public function showProof(
        FuelCustomerPayment $fuelCustomerPayment
    ) {
        // Apply the appropriate per-record viewing
        // authorization policy before production use.

        $imagePath = $fuelCustomerPayment->proof_image;

        if (!$imagePath) {
            abort(404, 'No payment proof attached.');
        }

        $disk = Storage::disk('payment_proofs');

        if (!$disk->exists($imagePath)) {
            abort(404, 'Payment proof image not found.');
        }

        $mimeType = $disk->mimeType($imagePath);

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ];

        if (!in_array(
            $mimeType,
            $allowedTypes,
            true
        )) {
            abort(404, 'Invalid payment proof format.');
        }

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
     * Remove a customer payment record.
     *
     * Preserve proof image when using SoftDeletes
     * for audit and possible restoration.
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