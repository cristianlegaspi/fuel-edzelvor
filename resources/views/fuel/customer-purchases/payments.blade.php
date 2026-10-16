
@extends('layouts.fuel')

@section('title', 'Customer Purchase Payments')
@section('page-title', 'Customer Purchase Payments')
@section('page-subtitle', $purchase->sales_order_no)

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                {{ $purchase->customer }}
            </h2>

            <p class="text-sm text-slate-500">
                Sales Order: {{ $purchase->sales_order_no }}
            </p>
        </div>

        <a href="{{ route('fuel-customer-purchases.index') }}"
           class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm">
            Back to Purchases
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card
            label="Total Payables"
            :value="'₱'.number_format($purchase->total_payables, 2)"
        />

        <x-stat-card
            label="Total Paid"
            :value="'₱'.number_format($purchase->payments->sum('amount'), 2)"
        />

        <x-stat-card
            label="Balance"
            :value="'₱'.number_format(max(0, $purchase->total_payables - $purchase->payments->sum('amount')), 2)"
        />
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">

        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">
            Customer Payment History
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-3 px-2">Tracking No.</th>
                        <th class="py-3 px-2">Date</th>
                        <th class="py-3 px-2">Amount</th>
                        <th class="py-3 px-2">Method</th>
                        <th class="py-3 px-2">Reference</th>
                        <th class="py-3 px-2">Payment Proof</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($purchase->payments as $payment)
                        <tr>
                            <td class="py-3 px-2">
                                {{ $payment->customer_payment_tracking_no ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                {{ optional($payment->payment_date)->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="py-3 px-2 font-medium">
                                ₱{{ number_format($payment->amount, 2) }}
                            </td>

                            <td class="py-3 px-2">
                                {{ $payment->payment_method ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                {{ $payment->reference_no ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                @if($payment->proof_image)
                                    <button
                                        type="button"
                                        onclick="openPaymentProof(@js(route('fuel-customer-payments.proof', $payment)))"
                                        class="text-amber-600 font-medium hover:underline">
                                        View Proof
                                    </button>
                                @else
                                    <span class="text-slate-400">No Image</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-slate-400">
                                No payments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(in_array(strtolower((string) $purchase->status), ['paid', 'overpaid']))
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-5 text-sm text-slate-500">
            This purchase is fully paid or overpaid. Additional payments are not available.
        </div>
    @else
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">

            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">
                Add Customer Payment
            </h3>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('fuel-customer-purchases.payments.store', $purchase) }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-xs text-slate-500 mb-1">
                            Payment Date
                        </label>
                        <input type="date"
                               name="payment_date"
                               value="{{ old('payment_date') }}"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-1">
                            Amount
                        </label>
                        <input type="number"
                               name="amount"
                               step="0.01"
                               min="0.01"
                               value="{{ old('amount') }}"
                               required
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-1">
                            Payment Method
                        </label>
                        <select name="payment_method"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                            <option value="">Select Method</option>
                            @foreach(['Cash', 'Check', 'Bank Transfer', 'Online Transfer', 'GCash', 'Others'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                                    {{ $method }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-1">
                            Reference No.
                        </label>
                        <input type="text"
                               name="reference_no"
                               value="{{ old('reference_no') }}"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs text-slate-500 mb-2">
                        Proof of Payment (Image Only)
                    </label>

                    <input type="file"
                           name="proof_image"
                           id="proof_image"
                           accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 p-2 text-sm">

                    <p class="text-xs text-slate-400 mt-1">
                        JPG, JPEG, PNG, WEBP, GIF. Maximum 5 MB.
                    </p>

                    <div id="selectedProofContainer" class="hidden mt-3 flex items-center gap-3">
                        <img id="selectedProofThumbnail"
                             class="h-16 w-16 rounded-lg border object-cover"
                             alt="Selected payment proof">

                        <button type="button"
                                id="previewSelectedProof"
                                class="text-amber-600 text-sm font-medium hover:underline">
                            Preview Image
                        </button>

                        <button type="button"
                                id="removeSelectedProof"
                                class="text-red-600 text-sm font-medium hover:underline">
                            Remove
                        </button>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="rounded-lg bg-amber-500 px-6 py-2.5 text-white text-sm font-medium hover:bg-amber-600">
                        Add Payment
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

{{-- Include the existing reusable proof modal partial here. --}}
@include('fuel.partials.payment-proof-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('proof_image');
    const container = document.getElementById('selectedProofContainer');
    const thumbnail = document.getElementById('selectedProofThumbnail');
    const previewButton = document.getElementById('previewSelectedProof');
    const removeButton = document.getElementById('removeSelectedProof');

    if (!input) return;

    let selectedUrl = null;

    function clearSelection() {
        input.value = '';
        if (selectedUrl) URL.revokeObjectURL(selectedUrl);
        selectedUrl = null;
        thumbnail.removeAttribute('src');
        container.classList.add('hidden');
    }

    input.addEventListener('change', function () {
        clearSelectionPreviewOnly();

        const file = input.files[0];
        if (!file) return;

        if (!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)
            || file.size > 5 * 1024 * 1024) {
            alert('Select a supported image smaller than 5 MB.');
            clearSelection();
            return;
        }

        selectedUrl = URL.createObjectURL(file);
        thumbnail.src = selectedUrl;
        container.classList.remove('hidden');
    });

    function clearSelectionPreviewOnly() {
        if (selectedUrl) URL.revokeObjectURL(selectedUrl);
        selectedUrl = null;
        thumbnail.removeAttribute('src');
        container.classList.add('hidden');
    }

    previewButton.addEventListener('click', function () {
        if (selectedUrl) window.openPaymentProof(selectedUrl);
    });

    removeButton.addEventListener('click', clearSelection);
});
</script>

@endsection
