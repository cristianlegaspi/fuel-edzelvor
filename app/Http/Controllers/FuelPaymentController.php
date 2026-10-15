<?php

namespace App\Http\Controllers;

use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

/**
 * Payments made TO suppliers against FuelSalesOrder.
 *
 * FuelSalesOrder::recalculateTotals() is handled automatically
 * through FuelPayment model events.
 */
class FuelPaymentController extends Controller
{
    /**
     * Display supplier payments.
     */
    public function index(Request $request)
    {
        $payments = FuelPayment::query()
            ->with('salesOrder')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', function ($orderQuery) use ($search) {
                            $orderQuery
                                ->where('sales_order_no', 'like', "%{$search}%")
                                ->orWhere('supplier', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view(
            'fuel.supplier-payments.index',
            compact('payments')
        );
    }

    /**
     * Store supplier payment with optional image proof.
     */
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
                // ->store('supplier-payment-proofs', 'local');
                ->store('supplier-payment-proofs', 'payment_proofs');

            if (!$imagePath) {
                return back()
                    ->withErrors([
                        'proof_image' => 'Unable to save payment proof image.',
                    ])
                    ->withInput();
            }

            $validated['proof_image'] = $imagePath;
        }

        try {
            $fuelSalesOrder->payments()->create($validated);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                // Storage::disk('local')->delete($imagePath);
                Storage::disk('payment_proofs')->delete($imagePath);
            }

            throw $exception;
        }

        return back()->with(
            'success',
            'Supplier payment recorded successfully.'
        );
    }

    /**
     * Display proof image securely.
     *
     * Images are stored in Laravel private local storage.
     * This endpoint is used by the draggable image modal.
     */

    public function showProof(FuelPayment $fuelPayment)
    {

        // Enforce your existing payment-view authorization policy here.

    $imagePath = $fuelPayment->proof_image;

    abort_if(!$imagePath, 404, 'No payment proof attached.');

    $disk = Storage::disk('payment_proofs');

    // New images are stored in Amazon S3.
    // Older images may still exist in the local storage.
    if (!$disk->exists($imagePath)) {
        $disk = Storage::disk('local');
    }

    abort_unless(
        $disk->exists($imagePath),
        404,
        'Payment proof image not found.'
    );

    $mimeType = $disk->mimeType($imagePath);

    abort_unless(in_array($mimeType, [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ], true), 404);

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
        // $imagePath = $fuelPayment->proof_image;

        // if (!$imagePath) {
        //     abort(404, 'No payment proof attached.');
        // }

        // $disk = Storage::disk('local');

        // if (!$disk->exists($imagePath)) {
        //     abort(404, 'Payment proof image not found.');
        // }

        // $mimeType = $disk->mimeType($imagePath);

        // $allowedTypes = [
        //     'image/jpeg',
        //     'image/png',
        //     'image/webp',
        //     'image/gif',
        // ];

        // if (!in_array($mimeType, $allowedTypes, true)) {
        //     abort(404, 'Invalid image format.');
        // }

        // // Keep the route authenticated and enforce
        // // payment-view authorization before production use.

        // return response()->file(
        //     $disk->path($imagePath),
        //     [
        //         'Content-Type' => $mimeType,
        //         'Content-Disposition' => 'inline',
        //         'X-Content-Type-Options' => 'nosniff',
        //         'Cache-Control' => 'private, no-store',
        //     ]
        // );
    }


    /**
     * Delete supplier payment.
     *
     * Maintains existing payment deletion behavior.
     */
    public function destroy(FuelPayment $fuelPayment)
    {

    $fuelPayment->delete();

    // Retain image evidence for audit and possible restoration.
    // The associated payment record is soft-deleted if the model
    // uses Laravel SoftDeletes.

    return back()->with(
        'success',
        'Supplier payment removed successfully.'
    );
        // $imagePath = $fuelPayment->proof_image;

        // $fuelPayment->delete();

        // if ($imagePath) {
        //     Storage::disk('local')->delete($imagePath);
        // }

        // return back()->with(
        //     'success',
        //     'Supplier payment removed successfully.'
        // );
    }
}
