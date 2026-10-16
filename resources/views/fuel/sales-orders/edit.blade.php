
@extends('layouts.fuel')

@section('title', $order->sales_order_no)
@section('page-title', $order->sales_order_no)
@section('page-subtitle', $order->supplier)

@section('header-actions')
    <x-badge :color="match($order->status) {
        'paid' => 'green',
        'partial' => 'yellow',
        'overpaid' => 'blue',
        default => 'red'
    }">
        {{ ucfirst($order->status) }}
    </x-badge>
@endsection

@section('content')

{{-- Supplier Order Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <x-stat-card
        label="Total Liters"
        :value="number_format($order->total_liters, 2).' L'"
    />

    <x-stat-card
        label="Gross Amount"
        :value="'₱'.number_format($order->gross_amount, 2)"
    />

    <x-stat-card
        label="Net Amount"
        :value="'₱'.number_format($order->net_amount, 2)"
    />

    <x-stat-card
        label="Balance"
        :value="'₱'.number_format($order->balance_amount, 2)"
        :color="$order->balance_amount > 0
            ? 'text-red-600 dark:text-red-400'
            : 'text-green-600 dark:text-green-400'"
    />
</div>

{{-- Supplier Order Information --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Edit Supplier Order
        </h2>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Update the supplier order information and fuel purchase details.
        </p>
    </div>

    <a
        href="{{ route('fuel-sales-orders.index') }}"
        class="inline-flex items-center rounded-lg border border-slate-300
               px-4 py-2 text-sm font-medium text-slate-700
               transition hover:bg-slate-100
               dark:border-slate-600 dark:text-slate-300
               dark:hover:bg-slate-800"
    >
        ← Back to Supplier Orders
    </a>
</div>

{{-- Edit Form --}}
<form
    method="POST"
    action="{{ route('fuel-sales-orders.update', $order) }}"
    data-confirm="Save changes to this supplier order?"
    data-confirm-title="Save changes"
>
    @csrf
    @method('PUT')

    @include('fuel.sales-orders._form')
</form>

@endsection
