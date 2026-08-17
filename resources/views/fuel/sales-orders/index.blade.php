@extends('layouts.fuel')

@section('title', 'Supplier Orders')
@section('page-title', 'Supplier Orders')
@section('page-subtitle', 'Fuel purchased from suppliers')

@section('header-actions')
    <a href="{{ route('fuel-sales-orders.create') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">+ New Order</a>
@endsection

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order no. or supplier..."
           class="w-full sm:w-72 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <option value="">All statuses</option>
        @foreach(['unpaid','partial','paid'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Order No.</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Date Ordered</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Supplier</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Total Liters</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Net Amount</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Balance</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Status</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($orders as $order)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-amber-600 dark:text-amber-400"><a href="{{ route('fuel-sales-orders.edit', $order) }}" class="hover:underline">{{ $order->sales_order_no }}</a></td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ optional($order->date_ordered)->format('M d, Y') }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $order->supplier }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ number_format($order->total_liters, 2) }} L</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($order->net_amount, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($order->balance_amount, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <x-badge :color="match($order->status) { 'paid' => 'green', 'partial' => 'yellow', default => 'red' }">{{ ucfirst($order->status) }}</x-badge>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right space-x-2">
                        <a href="{{ route('fuel-sales-orders.edit', $order) }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline">Edit</a>
                        <form action="{{ route('fuel-sales-orders.destroy', $order) }}" method="POST" class="inline" data-confirm="This will permanently delete the supplier order and its items. This cannot be undone." data-confirm-type="danger" data-confirm-title="Delete this order?">
                            @csrf @method('DELETE')
                            <button class="text-red-600 dark:text-red-400 text-sm font-medium hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No supplier orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $orders->links() }}</div>
@endsection
