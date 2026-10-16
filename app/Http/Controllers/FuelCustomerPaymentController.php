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
            'proof_image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max('5mb')],
        ], [
            'proof_image.required' => 'Receipt is required. Please attach proof of payment before saving.',
            'proof_image.image' => 'Only valid image receipts are allowed.',
            'proof_image.mimes' => 'Only JPG, PNG, WEBP, and GIF image receipts are accepted.',
            'proof_image.max' => 'The receipt must not exceed 5 MB.',
        ]);

        unset($validated['proof_image']);
        $uploadedPath = null;
        if ($request->hasFile('proof_image')) {
            // Shared disk: local in Laragon, private S3 in production.
            $uploadedPath = $request->file('proof_image')->store('customer-payment-proofs', 'payment_proofs');
            $validated['proof_image'] = $uploadedPath;
        }

        try {
            $fuelCustomerPurchase->payments()->create($validated);
        } catch (\Throwable $e) {
            if ($uploadedPath) Storage::disk('payment_proofs')->delete($uploadedPath);
            throw $e;
        }

        return redirect()->route('fuel-customer-purchases.index')->with('success', 'Customer payment recorded successfully.');
    }

    public function showProof(FuelCustomerPayment $fuelCustomerPayment)
    {
        // Route is protected by auth/account middleware. Add per-record authorization
        // here if your app has customer- or role-specific viewing restrictions.
        if (!$fuelCustomerPayment->proof_image) abort(404);
        $disk = Storage::disk('payment_proofs');
        $path = $fuelCustomerPayment->proof_image;
        if (!$disk->exists($path)) abort(404);
        $mime = $disk->mimeType($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) abort(404);
        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function destroy(FuelCustomerPayment $fuelCustomerPayment)
    {
        // SoftDeletes retains payment history. Do not delete the S3 proof here,
        // so authorized restores can still recover the corresponding attachment.
        $fuelCustomerPayment->delete();

        return back()->with('success', 'Payment removed.');
    }
}
