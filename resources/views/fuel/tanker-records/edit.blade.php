@extends('layouts.fuel')

@section('title', 'Tanker Record')
@section('page-title', $record->customerPurchase->tanker_details ?? 'Tanker Record')
@section('page-subtitle', 'SO: '.($record->customerPurchase->sales_order_no ?? '—'))

@section('header-actions')
    <a href="{{ route('fuel-tanker-records.index') }}" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:underline">&larr; Back to Tanker Purchases</a>
@endsection

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Liters" :value="number_format($record->customerPurchase->total_liters ?? 0, 2).' L'" />
    <x-stat-card label="Freight / Liter" :value="'₱'.number_format($record->getFreightPerLiter(), 2)" />
    <x-stat-card label="Income" :value="'₱'.number_format($record->getFreightIncome(), 2)" />
    <x-stat-card label="Net Income" :value="'₱'.number_format($record->net_income, 2)" :color="$record->net_income < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" />
</div>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Tanker / Sales Order Information</h2>
    <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">These details are automatically retrieved from the customer purchase.</p>
    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Tanker</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ $record->customerPurchase->tanker_details ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Sales Order No.</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ $record->customerPurchase->sales_order_no ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Date Ordered</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ optional($record->customerPurchase->date_ordered)->format('F d, Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Supplier</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ $record->customerPurchase->supplier ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Customer / Delivered To</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ $record->customerPurchase->customer ?? '—' }}</dd></div>
        <div><dt class="text-slate-400 dark:text-slate-500 text-xs">Liters</dt><dd class="text-slate-700 dark:text-slate-300 font-medium">{{ number_format($record->customerPurchase->total_liters ?? 0, 2) }} L</dd></div>
    </dl>
</div>

<form method="POST" action="{{ route('fuel-tanker-records.update', $record) }}" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4"
      data-confirm="Save changes to this tanker record?" data-confirm-title="Save changes">
    @csrf
    @method('PUT')

    <h2 class="font-semibold text-slate-900 dark:text-white">Driver and Expense Information</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Date Delivered</label>
            <input type="date" name="date_delivered" value="{{ optional($record->date_delivered)->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Driver Name</label>
            <input type="text" name="driver_name" value="{{ $record->driver_name }}" placeholder="Enter driver name" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Driver Salary</label>
            <input type="number" step="0.01" min="0" name="driver_salary" value="{{ $record->driver_salary }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Date Paid Driver</label>
            <input type="date" name="date_paid_driver" value="{{ optional($record->date_paid_driver)->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Other Expenses</label>
            <input type="number" step="0.01" min="0" name="other_expenses" value="{{ $record->other_expenses }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Net Income</label>
            <input type="text" value="₱{{ number_format($record->calculateNetIncome(), 2) }}" readonly class="w-full rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-sm text-slate-500 dark:text-slate-400">
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Income − Driver Salary − Other Expenses</p>
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Other Expenses Details</label>
            <textarea name="other_expenses_details" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">{{ $record->other_expenses_details }}</textarea>
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Remarks</label>
            <textarea name="remarks" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">{{ $record->remarks }}</textarea>
        </div>
    </div>

    <div class="pt-2">
        <button data-loading-text="Saving…" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Save Changes</button>
    </div>
</form>
@endsection
