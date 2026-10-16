<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelCustomerPayment;
use App\Models\FuelCustomerPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

/**
 * Customer payments, authorized corrections, receipt access and audit history.
 * FuelCustomerPayment model events recalculate related purchase totals.
 */
class FuelCustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = FuelCustomerPayment::query()
            ->with('customerPurchase')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('customer_payment_tracking_no', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('customerPurchase', function ($purchaseQuery) use ($search) {
                            $purchaseQuery->where('customer', 'like', "%{$search}%")
                                ->orWhere('sales_order_no', 'like', "%{$search}%");
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
        $request->merge(['reference_no' => trim((string) $request->input('reference_no', ''))]);
        $validated = $request->validateWithBag('customerPayment', [
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', 'string', Rule::in(['Cash', 'Check', 'Bank Transfer', 'Online Transfer', 'GCash', 'Others'])],
            'reference_no' => [
                'required', 'string', 'max:255',
                Rule::unique('fuel_customer_payments', 'reference_no'),
            ],
            'remarks' => ['nullable', 'string'],
            'proof_image' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max('5mb'),
            ],
        ]);

        unset($validated['proof_image']);
        $validated['reference_no'] = trim($validated['reference_no']);
        if ($validated['reference_no'] === '') {
            throw ValidationException::withMessages(['reference_no' => 'Reference No. is required.'])->errorBag('customerPayment');
        }

        $imagePath = null;
        try {
            $imagePath = $request->file('proof_image')->store('customer-payment-proofs', 'payment_proofs');
            if (!$imagePath) {
                throw ValidationException::withMessages(['proof_image' => 'Unable to save the receipt image.']);
            }
            $validated['proof_image'] = $imagePath;

            DB::transaction(function () use ($fuelCustomerPurchase, $validated) {
                $purchase = FuelCustomerPurchase::query()
                    ->lockForUpdate()
                    ->findOrFail($fuelCustomerPurchase->getKey());

                if (in_array(strtolower(trim((string) $purchase->status)), ['paid', 'overpaid'], true)) {
                    throw ValidationException::withMessages([
                        'amount' => 'This purchase is already paid or overpaid. Additional payments are disabled.',
                    ]);
                }

                $payableCents = (int) round((float) $purchase->total_payables * 100);
                $existingCents = (int) round((float) $purchase->payments()->sum('amount') * 100);
                $amountCents = (int) round((float) $validated['amount'] * 100);
                $remainingCents = $payableCents - $existingCents;
                if ($remainingCents <= 0 || $amountCents > $remainingCents) {
                    throw ValidationException::withMessages([
                        'amount' => 'Payment exceeds the remaining balance of ₱'.number_format(max(0, $remainingCents)/100, 2).'.',
                    ]);
                }

                // Re-check within the purchase lock. The DB unique index recommended
                // in the installation notes is still necessary for race protection.
                if (FuelCustomerPayment::withTrashed()
                    ->where('reference_no', $validated['reference_no'])->exists()) {
                    throw ValidationException::withMessages([
                        'reference_no' => 'The reference no. has already been taken.',
                    ]);
                }

                $purchase->payments()->create($validated);
            }, 3);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('payment_proofs')->delete($imagePath);
            }
            if ($exception instanceof ValidationException) {
                $exception->errorBag = 'customerPayment';
            }
            throw $exception;
        }

        return redirect()->route('fuel-customer-purchases.index')
            ->with('success', 'Customer payment recorded successfully.');
    }

    /** Authorized correction; does not modify payment tracking number or owner. */
    public function update(Request $request, FuelCustomerPayment $fuelCustomerPayment)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', 'string', Rule::in(['Cash', 'Check', 'Bank Transfer', 'Online Transfer', 'GCash', 'Others'])],
            'reference_no' => [
                'required', 'string', 'max:255',
                Rule::unique('fuel_customer_payments', 'reference_no')
                    ->ignore($fuelCustomerPayment->getKey()),
            ],
            'remarks' => ['nullable', 'string'],
            'edit_reason' => ['required', 'string', 'min:10', 'max:1000'],
            // An existing receipt may be retained on edit.
            'proof_image' => [
                'nullable',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max('5mb'),
            ],
        ], $this->editReasonMessages());

        $reason = trim($validated['edit_reason']);
        $validated['reference_no'] = trim($validated['reference_no']);
        if ($validated['reference_no'] === '') {
            throw ValidationException::withMessages(['reference_no' => 'Reference No. is required.']);
        }
        unset($validated['edit_reason'], $validated['proof_image']);

        $imagePath = null;
        try {
            if ($request->hasFile('proof_image')) {
                $imagePath = $request->file('proof_image')
                    ->store('customer-payment-proofs', 'payment_proofs');
                if (!$imagePath) {
                    throw ValidationException::withMessages([
                        'proof_image' => 'Unable to save the replacement receipt image.',
                    ]);
                }
                $validated['proof_image'] = $imagePath;
            }

            DB::transaction(function () use ($fuelCustomerPayment, $validated, $reason) {
                $payment = FuelCustomerPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($fuelCustomerPayment->getKey());

                $purchase = FuelCustomerPurchase::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->fuel_customer_purchase_id);

                if (FuelCustomerPayment::withTrashed()
                    ->where('reference_no', $validated['reference_no'])
                    ->whereKeyNot($payment->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'reference_no' => 'The reference no. has already been taken.',
                    ]);
                }

                $payableCents = (int) round((float) $purchase->total_payables * 100);
                $otherPaidCents = (int) round((float) $purchase->payments()
                    ->whereKeyNot($payment->getKey())->sum('amount') * 100);
                $correctedCents = (int) round((float) $validated['amount'] * 100);
                $maximumCents = max(0, $payableCents - $otherPaidCents);

                // Strict: never allow an edited payment to leave the purchase
                // overpaid, even if the purchase was overpaid before this edit.
                if ($correctedCents > $maximumCents) {
                    throw ValidationException::withMessages([
                        'amount' => 'Corrected amount exceeds the maximum allowable payment of ₱'.number_format($maximumCents/100,2).'.',
                    ]);
                }

                $payment->fill($validated);
                if (!$payment->isDirty()) {
                    throw ValidationException::withMessages([
                        'edit_reason' => 'No payment details were changed.',
                    ]);
                }

                // The supplied Auditable trait records the actual updated values.
                // Include the correction reason on the SAME log rather than
                // writing a second, duplicate audit entry.
                $payment->auditEditReason = $reason;
                $payment->save();
            }, 3);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('payment_proofs')->delete($imagePath);
            }
            throw $exception;
        }

        // Do not delete the old receipt: it remains historical evidence.
        return redirect()->route('fuel-customer-purchases.index')
            ->with('success', 'Payment correction saved and recorded in Audit Logs.');
    }

    public function history(Request $request, FuelCustomerPayment $fuelCustomerPayment): JsonResponse
    {
        // History contains financial data, user information and previous
        // receipt locations. Restrict to administrators.
        $this->requireAdmin($request);

        $logs = $fuelCustomerPayment->auditLogs()
            ->where('action', 'updated')
            ->with('user')
            ->latest('id')
            ->get();

        return response()->json($logs->map(function (AuditLog $log) {
            $old = (array) ($log->old_values ?? []);
            $new = (array) ($log->new_values ?? []);
            $changes = [];
            foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
                if (in_array($field, ['password', 'remember_token'], true)) {
                    continue;
                }
                $changes[] = [
                    'field' => $field,
                    'old' => $old[$field] ?? null,
                    'new' => $new[$field] ?? null,
                ];
            }

            return [
                'date' => $log->created_at?->format('M d, Y h:i A'),
                'user' => $log->user?->name ?? 'Unknown user',
                'action' => $log->actionLabel(),
                'description' => $log->description,
                'changes' => $changes,
            ];
        })->values());
    }

    public function showProof(FuelCustomerPayment $fuelCustomerPayment)
    {
        // Add a record-specific view policy here if required by your roles.
        $path = $fuelCustomerPayment->proof_image;
        abort_if(!$path, 404, 'No receipt attached.');

        // New uploads are on the payment_proofs disk; older local uploads can
        // still be retrieved during the S3 migration.
        $disk = Storage::disk('payment_proofs');
        if (!$disk->exists($path)) {
            $disk = Storage::disk('local');
        }
        abort_unless($disk->exists($path), 404, 'Receipt image not found.');

        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        ], true), 404, 'Unsupported receipt image format.');

        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function destroy(Request $request, FuelCustomerPayment $fuelCustomerPayment)
    {
        $this->requireAdmin($request);

        DB::transaction(function () use ($fuelCustomerPayment) {
            $payment = FuelCustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($fuelCustomerPayment->getKey());
            FuelCustomerPurchase::query()->lockForUpdate()
                ->findOrFail($payment->fuel_customer_purchase_id);
            // Soft delete; retain the receipt in storage for recovery/audit.
            $payment->delete();
        }, 3);

        return back()->with('success', 'Customer payment removed and logged.');
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && method_exists($request->user(), 'isAdminOrAbove')
                && $request->user()->isAdminOrAbove(),
            403,
            'Administrator authorization required.'
        );
    }

    private function editReasonMessages(): array
    {
        return [
            'edit_reason.required' => 'Please provide a reason for editing this payment.',
            'edit_reason.min' => 'The edit reason must contain at least 10 characters.',
        ];
    }
}
