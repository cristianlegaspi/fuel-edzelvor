@extends('layouts.fuel')

@section('title', 'Tanker Purchases')
@section('page-title', 'Tanker Purchases')
@section('page-subtitle', 'Delivery, driver, and freight income per tanker trip')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tanker, driver, or customer..."
           class="w-full sm:w-80 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Date Delivered</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Tanker</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Customer</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Liters</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Driver</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Income</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Driver Salary</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Other Expenses</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Net Income</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($records as $record)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ optional($record->date_delivered)->format('M d, Y') ?? 'Not entered' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-amber-600 dark:text-amber-400">
                        <a href="{{ route('fuel-tanker-records.edit', $record) }}" class="hover:underline">{{ $record->customerPurchase->tanker_details ?? '—' }}</a>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $record->customerPurchase->customer ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ number_format($record->customerPurchase->total_liters ?? 0, 2) }} L</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $record->driver_name ?? 'Not entered' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-800 dark:text-white">₱{{ number_format($record->getFreightIncome(), 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($record->driver_salary, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($record->other_expenses, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium {{ $record->net_income < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">₱{{ number_format($record->net_income, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-right">
                        <a href="{{ route('fuel-tanker-records.edit', $record) }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline">Update</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No tanker records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $records->links() }}</div>
@endsection
