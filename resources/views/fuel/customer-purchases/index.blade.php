
@extends('layouts.fuel')

@section('title', 'Customer Purchases')
@section('page-title', 'Customer Purchases')
@section('page-subtitle', 'Fuel resold to customers')

@section('header-actions')
    <a href="{{ route('fuel-customer-purchases.create') }}"
       class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
        + New Purchase
    </a>
@endsection

@section('content')

<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search customer or SO no..."
        class="w-full sm:w-72 rounded-lg border-slate-300 dark:border-slate-600 text-sm"
    >

    <select
        name="status"
        onchange="this.form.submit()"
        class="rounded-lg border-slate-300 dark:border-slate-600 text-sm"
    >
        <option value="">All statuses</option>

        @foreach(['unpaid', 'partial', 'paid', 'overpaid'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>
                {{ ucfirst($s) }}
            </option>
        @endforeach
    </select>

    <button
        type="submit"
        class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60"
    >
        Filter
    </button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">

        <table class="w-full text-sm">

            <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Customer</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Sales Order</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Total Liters</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Payables</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Net Income</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Status</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                @forelse($purchases as $purchase)

                    @php
                        $isPaid = strtolower(trim((string) $purchase->status)) === 'paid';
                    @endphp

                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">

                        {{-- Customer --}}
                        <td class="px-6 py-3 whitespace-nowrap font-medium">

                            @if($isPaid)
                                <span class="text-slate-600 dark:text-slate-300">
                                    {{ $purchase->customer }}
                                </span>
                            @else
                                <a
                                    href="{{ route('fuel-customer-purchases.edit', $purchase) }}"
                                    class="text-amber-600 dark:text-amber-400 hover:underline"
                                >
                                    {{ $purchase->customer }}
                                </a>
                            @endif

                        </td>

                        {{-- Sales Order --}}
                        <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ $purchase->sales_order_no }}
                        </td>

                        {{-- Total Liters --}}
                        <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ number_format($purchase->total_liters, 2) }} L
                        </td>

                        {{-- Payables --}}
                        <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            ₱{{ number_format($purchase->total_payables, 2) }}
                        </td>

                        {{-- Net Income --}}
                        <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            ₱{{ number_format($purchase->net_income, 2) }}
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-3 whitespace-nowrap">

                            <x-badge :color="match($purchase->status) {
                                'paid' => 'green',
                                'partial' => 'yellow',
                                'overpaid' => 'blue',
                                default => 'red'
                            }">
                                {{ ucfirst($purchase->status) }}
                            </x-badge>

                        </td>

                        
            {{-- Actions --}}
            <td class="px-6 py-3 whitespace-nowrap text-right">

                @php
                    $isPaid = strtolower(trim((string) $purchase->status)) === 'paid';
                @endphp

                <div class="inline-flex items-center gap-3">

                    @if($isPaid)

                        {{-- Paid: View Details Only --}}
                        <a
                            href="{{ route('fuel-customer-purchases.details', $purchase) }}"
                            class="text-blue-600 dark:text-blue-400 text-sm font-medium hover:underline"
                        >
                            View Details
                        </a>

                        <span
                            class="inline-flex items-center gap-1 text-xs font-medium text-slate-400"
                            title="This purchase is fully paid and locked."
                        >
                            🔒 Locked
                        </span>

                    @else

                        {{-- Unpaid / Partial / Overpaid --}}
                        <a
                            href="{{ route('fuel-customer-purchases.edit', $purchase) }}"
                            class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline"
                        >
                            Edit
                        </a>

                        <form
                            action="{{ route('fuel-customer-purchases.destroy', $purchase) }}"
                            method="POST"
                            class="inline"
                            data-confirm="This will permanently delete the customer purchase and its items."
                            data-confirm-type="danger"
                            data-confirm-title="Delete this purchase?"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="text-red-600 dark:text-red-400 text-sm font-medium hover:underline"
                            >
                                Delete
                            </button>
                        </form>

                    @endif

                </div>
            </td>


                    </tr>

                @empty

                    <tr>
                        <td colspan="7"
                            class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                            No customer purchases found.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    <x-pagination :paginator="$purchases" />
</div>

@endsection
