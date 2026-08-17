@extends('layouts.fuel')

@section('title', $order->sales_order_no)
@section('page-title', $order->sales_order_no)
@section('page-subtitle', $order->supplier)

@section('header-actions')
    <x-badge :color="match($order->status) { 'paid' => 'green', 'partial' => 'yellow', default => 'red' }">{{ ucfirst($order->status) }}</x-badge>
@endsection

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Total Liters" :value="number_format($order->total_liters, 2).' L'" />
    <x-stat-card label="Gross Amount" :value="'₱'.number_format($order->gross_amount, 2)" />
    <x-stat-card label="Net Amount" :value="'₱'.number_format($order->net_amount, 2)" />
    <x-stat-card label="Balance" :value="'₱'.number_format($order->balance_amount, 2)" :color="$order->balance_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" />
</div>

<form method="POST" action="{{ route('fuel-sales-orders.update', $order) }}"
      data-confirm="Save changes to this supplier order?" data-confirm-title="Save changes">
    @csrf
    @method('PUT')
    @include('fuel.sales-orders._form')
</form>

{{-- Payments made to this supplier --}}
<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mt-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Payments to Supplier</h2>

    <table class="w-full text-sm mb-6">
        <thead class="text-left text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
            <tr><th class="py-2 font-medium">Date</th><th class="py-2 font-medium">Amount</th><th class="py-2 font-medium">Method</th><th class="py-2 font-medium">Reference</th><th class="py-2 font-medium text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($order->payments as $payment)
                <tr>
                    <td class="py-2">{{ optional($payment->payment_date)->format('M d, Y') }}</td>
                    <td class="py-2">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ $payment->payment_method }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ $payment->reference_no }}</td>
                    <td class="py-2 text-right">
                        <form action="{{ route('fuel-payments.destroy', $payment) }}" method="POST" data-confirm="This payment record will be permanently removed." data-confirm-type="danger" data-confirm-title="Remove this payment?">
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

    <form method="POST" action="{{ route('fuel-sales-orders.payments.store', $order) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end border-t border-slate-100 dark:border-slate-800 pt-4"
          data-confirm="Add this payment to the supplier order?" data-confirm-title="Add payment">
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
@endsection
