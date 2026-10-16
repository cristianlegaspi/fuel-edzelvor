<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelPayment;
use App\Models\FuelSalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class FuelPaymentController extends Controller
{
    private const METHODS = ['Cash', 'Check', 'Bank Transfer', 'Online Transfer', 'GCash', 'Others'];

    public function index(Request $request)
    {
        $payments = FuelPayment::query()->with('salesOrder')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', fn ($sq) => $sq->where('sales_order_no', 'like', "%{$search}%")
                            ->orWhere('supplier', 'like', "%{$search}%"));
                });
            })->latest('payment_date')->paginate(15)->withQueryString();
        return view('fuel.supplier-payments.index', compact('payments'));
    }

    public function store(Request $request, FuelSalesOrder $fuelSalesOrder)
    {
        $request->merge(['reference_no' => trim((string) $request->input('reference_no', ''))]);
        $validated = $request->validateWithBag('supplierPayment', [
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'reference_no' => ['required', 'string', 'max:255', Rule::unique('fuel_payments', 'reference_no')],
            'remarks' => ['nullable', 'string'],
            'proof_image' => ['required', File::image()->types(['jpg','jpeg','png','webp','gif'])->max('5mb')],
            'payment_order_id' => ['nullable', 'integer'],
        ]);
        unset($validated['proof_image'], $validated['payment_order_id']);
        $uploadedPath = null;
        try {
            DB::transaction(function () use ($request, $fuelSalesOrder, $validated, &$uploadedPath) {
                $order = FuelSalesOrder::query()->lockForUpdate()->findOrFail($fuelSalesOrder->getKey());
                if (in_array(strtolower(trim((string) $order->status)), ['paid','overpaid'], true)) {
                    throw ValidationException::withMessages(['amount' => 'This supplier order is paid or overpaid.']);
                }
                $remaining = (int) round((float) $order->net_amount * 100)
                    - (int) round((float) $order->payments()->sum('amount') * 100);
                $newAmount = (int) round((float) $validated['amount'] * 100);
                if ($remaining <= 0 || $newAmount > $remaining) {
                    throw ValidationException::withMessages(['amount' => 'Payment exceeds remaining balance.']);
                }
                // Historical references should never be reused; check withTrashed when available.
                $references = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(FuelPayment::class), true)
                    ? FuelPayment::withTrashed() : FuelPayment::query();
                if ($references->where('reference_no', $validated['reference_no'])->exists()) {
                    throw ValidationException::withMessages(['reference_no' => 'This Reference No. is already used by another supplier payment.']);
                }
                $uploadedPath = $request->file('proof_image')->store('supplier-payment-proofs', 'payment_proofs');
                if (!$uploadedPath) throw ValidationException::withMessages(['proof_image' => 'Unable to save receipt.']);
                $order->payments()->create($validated + ['proof_image' => $uploadedPath]);
            }, 3);
        } catch (\Throwable $e) {
            if ($uploadedPath) Storage::disk('payment_proofs')->delete($uploadedPath);
            if ($e instanceof ValidationException) {
                $e->errorBag = 'supplierPayment';
                throw $e;
            }
            Log::error('Supplier payment failed', ['order_id' => $fuelSalesOrder->getKey(), 'error' => $e->getMessage()]);
            return back()->withErrors(['payment' => 'Unable to record supplier payment.'], 'supplierPayment')
                ->withInput($request->except('proof_image'));
        }
        return redirect()->route('fuel-sales-orders.index')->with('success', 'Supplier payment recorded.');
    }

    public function update(Request $request, FuelPayment $fuelPayment)
    {
        $this->requireAdmin($request);
        $request->merge(['reference_no' => trim((string) $request->input('reference_no', ''))]);
        $validated = $request->validateWithBag('supplierCorrection', [
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'reference_no' => ['required','string','max:255', Rule::unique('fuel_payments','reference_no')->ignore($fuelPayment->getKey())],
            'remarks' => ['nullable','string'],
            'edit_reason' => ['required','string','min:10','max:1000'],
            'proof_image' => ['nullable', File::image()->types(['jpg','jpeg','png','webp','gif'])->max('5mb')],
            'payment_order_id' => ['nullable','integer'],
            'payment_edit_url' => ['nullable','string','max:2048'],
        ]);
        $reason = trim($validated['edit_reason']);
        unset($validated['edit_reason'], $validated['proof_image'], $validated['payment_order_id'], $validated['payment_edit_url']);
        $uploadedPath = null;
        try {
            if ($request->hasFile('proof_image')) {
                $uploadedPath = $request->file('proof_image')->store('supplier-payment-proofs', 'payment_proofs');
                if (!$uploadedPath) throw ValidationException::withMessages(['proof_image' => 'Unable to save replacement receipt.']);
                $validated['proof_image'] = $uploadedPath;
            }
            DB::transaction(function () use ($fuelPayment, $validated, $reason) {
                // Lock parent first to serialize alongside new payments.
                $orderId = $fuelPayment->fuel_sales_order_id;
                $order = FuelSalesOrder::query()->lockForUpdate()->findOrFail($orderId);
                $payment = FuelPayment::query()->lockForUpdate()->findOrFail($fuelPayment->getKey());
                abort_unless((int)$payment->fuel_sales_order_id === (int)$order->getKey(), 409);
                $references = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(FuelPayment::class), true)
                    ? FuelPayment::withTrashed() : FuelPayment::query();
                if ($references->where('reference_no', $validated['reference_no'])->whereKeyNot($payment->getKey())->exists()) {
                    throw ValidationException::withMessages(['reference_no' => 'This Reference No. is already used by another supplier payment.']);
                }
                $others = (int) round((float) $order->payments()->whereKeyNot($payment->getKey())->sum('amount') * 100);
                $oldTotal = $others + (int) round((float) $payment->amount * 100);
                $newTotal = $others + (int) round((float) $validated['amount'] * 100);
                $net = (int) round((float) $order->net_amount * 100);
                if ($newTotal > $net && $newTotal > $oldTotal) {
                    throw ValidationException::withMessages(['amount' => 'Correction cannot create or increase an overpayment.']);
                }
                $payment->fill($validated);
                if (!$payment->isDirty()) throw ValidationException::withMessages(['edit_reason' => 'No payment fields changed.']);
                $payment->auditEditReason = $reason;
                $payment->save();
            }, 3);
        } catch (\Throwable $e) {
            if ($uploadedPath) Storage::disk('payment_proofs')->delete($uploadedPath);
            if ($e instanceof ValidationException) $e->errorBag = 'supplierCorrection';
            throw $e;
        }
        // Old receipt is intentionally retained for audit evidence.
        return redirect()->route('fuel-sales-orders.index')->with('success', 'Supplier payment correction saved and audited.');
    }

    public function history(Request $request, FuelPayment $fuelPayment): JsonResponse
    {
        $this->requireAdmin($request);
        abort_unless(method_exists($fuelPayment, 'auditLogs'), 500, 'FuelPayment must use Auditable trait.');
        $logs = $fuelPayment->auditLogs()->where('action','updated')->with('user')->latest('id')->get();
        return response()->json($logs->map(function (AuditLog $log) {
            $old = (array)($log->old_values ?? []);
            $new = (array)($log->new_values ?? []);
            $changes = [];
            foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
                if (in_array($field, ['password','remember_token'], true)) continue;
                $changes[] = ['field'=>$field, 'old'=>$old[$field] ?? null, 'new'=>$new[$field] ?? null];
            }
            return [
                'date'=>$log->created_at?->format('M d, Y h:i A'),
                'user'=>$log->user?->name ?? 'Unknown user',
                'action'=>$log->actionLabel(),
                'description'=>$log->description,
                'changes'=>$changes,
            ];
        })->values());
    }

    public function showProof(FuelPayment $fuelPayment)
    {
        $path = $fuelPayment->proof_image;
        abort_if(!$path, 404);
        $disk = Storage::disk('payment_proofs');
        if (!$disk->exists($path)) $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true), 404);
        return $disk->response($path, null, [
            'Content-Type'=>$mime, 'Content-Disposition'=>'inline',
            'Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff'
        ], 'inline');
    }

    public function destroy(Request $request, FuelPayment $fuelPayment)
    {
        $this->requireAdmin($request);
        // Existing application soft-deletes if FuelPayment uses SoftDeletes.
        DB::transaction(function () use ($fuelPayment) {
            FuelSalesOrder::query()->lockForUpdate()->findOrFail($fuelPayment->fuel_sales_order_id);
            $fuelPayment->delete();
        }, 3);
        return back()->with('success','Supplier payment removed.');
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user() && method_exists($request->user(), 'isAdminOrAbove')
            && $request->user()->isAdminOrAbove(), 403, 'Administrator authorization required.');
    }
}
