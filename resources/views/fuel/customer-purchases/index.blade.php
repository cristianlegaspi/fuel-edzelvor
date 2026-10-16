
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
<td class="px-4 py-3 whitespace-nowrap text-right">

    @php
        $status = strtolower(trim((string) $purchase->status));
        $isPaid = $status === 'paid';
        $isOverpaid = $status === 'overpaid';
    @endphp

    <div class="inline-flex items-center justify-end gap-2">

        @if($isPaid)

            {{-- VIEW DETAILS - BLUE --}}
            <a
                href="{{ route('fuel-customer-purchases.details', $purchase) }}"
                title="View Customer Purchase Details"
                style="background-color:#2563eb;color:#ffffff;"
                class="inline-flex items-center gap-2 rounded-lg
                       px-3 py-2 text-xs font-semibold
                       shadow-sm transition-all duration-200
                       hover:!bg-blue-500 hover:shadow-lg
                       hover:-translate-y-0.5"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M2.062 12.348a1 1 0 0 1 0-.696
                             10.75 10.75 0 0 1 19.876 0
                             1 1 0 0 1 0 .696
                             10.75 10.75 0 0 1-19.876 0"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                View Details
            </a>

            {{-- LOCKED - SLATE --}}
            <span
                title="Fully paid purchase - Editing disabled"
                style="background-color:#334155;color:#cbd5e1;"
                class="inline-flex items-center gap-2 rounded-lg
                       px-3 py-2 text-xs font-semibold
                       cursor-not-allowed select-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <rect x="5" y="11" width="14" height="10" rx="2"/>
                    <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                </svg>
                Locked
            </span>

        @else

            {{-- EDIT - AMBER --}}
            <a
                href="{{ route('fuel-customer-purchases.edit', $purchase) }}"
                title="Edit Customer Purchase"
                style="background-color:#d97706;color:#ffffff;"
                class="inline-flex items-center gap-2 rounded-lg
                       px-3 py-2 text-xs font-semibold
                       shadow-sm transition-all duration-200
                       hover:!bg-amber-500 hover:shadow-lg
                       hover:-translate-y-0.5"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
                Edit
            </a>

            {{-- ADD PAYMENT - GREEN --}}
            <a
                href="{{ route('fuel-customer-purchases.payments', $purchase) }}"
                title="{{ $isOverpaid ? 'View Customer Payments' : 'Add Customer Payment' }}"
                style="background-color:#059669;color:#ffffff;"
                class="inline-flex items-center gap-2 rounded-lg
                       px-3 py-2 text-xs font-semibold
                       shadow-sm transition-all duration-200
                       hover:!bg-emerald-500 hover:shadow-lg
                       hover:-translate-y-0.5"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                    <path d="M16 14h2"/>
                </svg>

                {{ $isOverpaid ? 'View Payments' : 'Add Payment' }}
            </a>

            {{-- DELETE - RED --}}
            <form
                action="{{ route('fuel-customer-purchases.destroy', $purchase) }}"
                method="POST"
                class="inline-flex"
                data-confirm="This will permanently delete the customer purchase and its items."
                data-confirm-type="danger"
                data-confirm-title="Delete this purchase?"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    title="Delete Customer Purchase"
                    style="background-color:#dc2626;color:#ffffff;"
                    class="inline-flex items-center gap-2 rounded-lg
                           px-3 py-2 text-xs font-semibold
                           shadow-sm transition-all duration-200
                           hover:!bg-red-500 hover:shadow-lg
                           hover:-translate-y-0.5"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4h8v2"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v5M14 11v5"/>
                    </svg>
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


<style>
    .purchase-action-btn {
        transition:
            background-color 0.2s ease,
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .purchase-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .purchase-action-edit:hover {
        background-color: #f59e0b !important;
    }

    .purchase-action-payment:hover {
        background-color: #10b981 !important;
    }

    .purchase-action-delete:hover {
        background-color: #ef4444 !important;
    }

    .purchase-action-view:hover {
        background-color: #3b82f6 !important;
    }
</style>


@endsection
