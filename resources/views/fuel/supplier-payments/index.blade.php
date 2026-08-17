@extends('layouts.fuel')

@section('title', 'Supplier Payments')
@section('page-title', 'Supplier Payments')
@section('page-subtitle', 'All payments made to suppliers, across every sales order')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reference no., SO no., or supplier..."
           class="w-full sm:w-80 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Payment Date</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Sales Order</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Supplier</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Amount</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Method</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Reference No.</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($payments as $payment)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ optional($payment->payment_date)->format('M d, Y') ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-amber-600 dark:text-amber-400">
                        @if($payment->salesOrder)
                            <a href="{{ route('fuel-sales-orders.edit', $payment->salesOrder) }}" class="hover:underline">{{ $payment->salesOrder->sales_order_no }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->salesOrder->supplier ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-800 dark:text-white">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->payment_method ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->reference_no ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-right space-x-2">
                        @if($payment->salesOrder)
                            <a href="{{ route('fuel-payments.soa.print', $payment->salesOrder) }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline" target="_blank">SOA</a>
                        @endif
                        <form action="{{ route('fuel-payments.destroy', $payment) }}" method="POST" class="inline" data-confirm="This payment record will be permanently removed." data-confirm-type="danger" data-confirm-title="Remove this payment?">
                            @csrf @method('DELETE')
                            <button class="text-red-600 dark:text-red-400 text-sm font-medium hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No supplier payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
