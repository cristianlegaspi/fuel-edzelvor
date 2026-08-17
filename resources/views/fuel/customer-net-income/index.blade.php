@extends('layouts.fuel')

@section('title', 'Customer Net Income')
@section('page-title', 'Customer Net Income')
@section('page-subtitle', 'Monthly net income and balance per customer for '.$year)

@section('header-actions')
    <a href="{{ route('fuel-customer-net-income.print-summary', request()->only(['year', 'customer'])) }}"
       target="_blank"
       class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Print</a>
@endsection

@section('content')
<form method="GET" class="mb-6 flex flex-wrap gap-3">
    <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        @foreach(range(now()->year, now()->year - 5) as $y)
            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
        @endforeach
    </select>
    <select name="customer" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <option value="all" @selected($selectedCustomer === '' || strtolower($selectedCustomer) === 'all')>All customers</option>
        @foreach($customers as $customer)
            <option value="{{ $customer }}" @selected($selectedCustomer === $customer)>{{ $customer }}</option>
        @endforeach
    </select>
</form>

<div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
    <x-stat-card label="Total Net Income ({{ $year }})" :value="'₱'.number_format($grandTotal['net_income'], 2)" :color="$grandTotal['net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" />
    <x-stat-card label="Total Balance / Short / Over" :value="'₱'.number_format($grandTotal['balance'], 2)" :color="$grandTotal['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" />
</div>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-4 py-3 font-medium sticky left-0 bg-slate-50 dark:bg-slate-800">Customer</th>
                @foreach($months as $month)
                    <th class="px-4 py-3 font-medium text-right whitespace-nowrap">{{ $month['name'] ?? $month }}</th>
                @endforeach
                <th class="px-4 py-3 font-medium text-right whitespace-nowrap">Total Net Income</th>
                <th class="px-4 py-3 font-medium text-right whitespace-nowrap">Total Balance</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($summaryRows as $row)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-4 py-3 font-medium text-slate-800 dark:text-white sticky left-0 bg-white dark:bg-slate-900">{{ $row['customer'] }}</td>
                    @foreach($row['monthly'] as $monthNo => $month)
                        <td class="px-4 py-3 text-right {{ $month['net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-300' }}">
                            {{ $month['net_income'] != 0 ? '₱'.number_format($month['net_income'], 2) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-semibold {{ $row['total_net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">₱{{ number_format($row['total_net_income'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $row['total_balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">₱{{ number_format($row['total_balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ count($months) + 3 }}" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No purchases found for {{ $year }}.</td></tr>
            @endforelse
        </tbody>
        @if($summaryRows->isNotEmpty())
            <tfoot class="border-t-2 border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                <tr>
                    <td class="px-4 py-3 font-semibold text-slate-800 dark:text-white sticky left-0 bg-slate-50 dark:bg-slate-800">Grand Total</td>
                    @foreach($grandMonthly as $month)
                        <td class="px-4 py-3 text-right font-medium {{ $month['net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' }}">
                            {{ $month['net_income'] != 0 ? '₱'.number_format($month['net_income'], 2) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-bold {{ $grandTotal['net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">₱{{ number_format($grandTotal['net_income'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $grandTotal['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">₱{{ number_format($grandTotal['balance'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
@endsection
