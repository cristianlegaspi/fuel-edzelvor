@extends('layouts.fuel')

@section('title', $purchase->customer)
@section('page-title', $purchase->customer)
@section('page-subtitle', 'SO: '.$purchase->sales_order_no)

@section('header-actions')
    <x-badge :color="match($purchase->status) { 'paid' => 'green', 'partial' => 'yellow', 'overpaid' => 'blue', default => 'red' }">{{ ucfirst($purchase->status) }}</x-badge>
@endsection

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Total Liters" :value="number_format($purchase->total_liters, 2).' L'" />
    <x-stat-card label="Payables" :value="'₱'.number_format($purchase->total_payables, 2)" />
    <x-stat-card label="Net Income" :value="'₱'.number_format($purchase->net_income, 2)" color="text-green-600 dark:text-green-400" />
    <x-stat-card label="Balance / Short / Over" :value="'₱'.number_format($purchase->balance_short_over, 2)" :color="$purchase->balance_short_over < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" />
</div>

<form method="POST" action="{{ route('fuel-customer-purchases.update', $purchase) }}"
      data-confirm="Save changes to this customer purchase?" data-confirm-title="Save changes">
    @csrf
    @method('PUT')
    @include('fuel.customer-purchases._form')
</form>

{{-- Customer payments --}}
<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mt-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Customer Payments</h2>

    <table class="w-full text-sm mb-6">
        <thead class="text-left text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
            <tr><th class="py-2 font-medium">Tracking No.</th><th class="py-2 font-medium">Date</th><th class="py-2 font-medium">Amount</th><th class="py-2 font-medium">Method</th><th class="py-2 font-medium text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($purchase->payments as $payment)
                <tr>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ $payment->customer_payment_tracking_no }}</td>
                    <td class="py-2">{{ optional($payment->payment_date)->format('M d, Y') }}</td>
                    <td class="py-2">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ $payment->payment_method }}</td>
                    <td class="py-2 text-right">
                        <form action="{{ route('fuel-customer-payments.destroy', $payment) }}" method="POST" data-confirm="This payment record will be permanently removed." data-confirm-type="danger" data-confirm-title="Remove this payment?">
                            @csrf @method('DELETE')
                            <button class="text-red-600 dark:text-red-400 text-xs font-medium hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-slate-400 dark:text-slate-500">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <form method="POST" action="{{ route('fuel-customer-purchases.payments.store', $purchase) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end border-t border-slate-100 dark:border-slate-800 pt-4"
          data-confirm="Add this payment to the customer purchase?" data-confirm-title="Add payment">
        @csrf
        <div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Date</label><input type="date" name="payment_date" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm"></div>
        <div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Amount</label><input type="number" step="0.01" name="amount" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm"></div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Method</label>
            <select name="payment_method" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                <option value="">Select</option>
                @foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Reference No.</label><input type="text" name="reference_no" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm"></div>
        <button data-loading-text="Adding…" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Add Payment</button>
    </form>
</div>

{{-- Tanker delivery record — only exists once tanker_details is filled in --}}
@if($purchase->tankerRecord)
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mt-6">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-1">Tanker Delivery Record</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Net income here = freight income earned minus driver salary and other expenses.</p>

        <form method="POST" action="{{ route('fuel-tanker-records.update', $purchase->tankerRecord) }}" class="grid grid-cols-1 md:grid-cols-3 gap-5"
              data-confirm="Save tanker delivery details?" data-confirm-title="Save changes">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date Delivered</label>
                <input type="date" name="date_delivered" value="{{ optional($purchase->tankerRecord->date_delivered)->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Driver Name</label>
                <input type="text" name="driver_name" value="{{ $purchase->tankerRecord->driver_name }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Driver Salary</label>
                <input type="number" step="0.01" name="driver_salary" value="{{ $purchase->tankerRecord->driver_salary }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date Paid Driver</label>
                <input type="date" name="date_paid_driver" value="{{ optional($purchase->tankerRecord->date_paid_driver)->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Other Expenses</label>
                <input type="number" step="0.01" name="other_expenses" value="{{ $purchase->tankerRecord->other_expenses }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Net Income</label>
                <p class="pt-2 font-semibold {{ $purchase->tankerRecord->net_income >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">₱{{ number_format($purchase->tankerRecord->net_income, 2) }}</p>
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Other Expenses Details</label>
                <textarea name="other_expenses_details" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">{{ $purchase->tankerRecord->other_expenses_details }}</textarea>
            </div>
            <div class="md:col-span-3">
                <button data-loading-text="Saving…" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Save Tanker Record</button>
            </div>
        </form>
    </div>
@endif
@endsection
