@extends('layouts.fuel')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Supplier and customer overview')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-10">
    <x-stat-card label="Supplier Orders" :value="$stats['totalSalesOrders']" :sublabel="$stats['unpaidSalesOrders'].' unpaid'" />
    <x-stat-card label="Supplier Balance Owed" :value="'₱'.number_format($stats['totalSupplierBalance'], 2)" color="text-red-600 dark:text-red-400" />
    <x-stat-card label="Customer Purchases" :value="$stats['totalCustomerPurchases']" :sublabel="$stats['unpaidCustomerPurchases'].' unpaid'" />
    <x-stat-card label="Total Net Income" :value="'₱'.number_format($stats['totalNetIncome'], 2)" color="text-green-600 dark:text-green-400" />
</div>

{{-- ===================== Overall Business Health ===================== --}}
<div class="mb-4">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Overall Business Health</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Net income
        @if($netIncomeKpi['changePct'] !== null && $netIncomeKpi['changePct'] >= 0)
            <em class="not-italic font-semibold text-green-600 dark:text-green-400">is trending up</em>
        @elseif($netIncomeKpi['changePct'] !== null)
            <em class="not-italic font-semibold text-red-600 dark:text-red-400">has softened</em>
        @else
            <em class="not-italic font-semibold text-slate-700 dark:text-slate-300">is being tracked</em>
        @endif
        month over month, following {{ $stats['totalCustomerPurchases'] }} recorded customer purchases.
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-10">
    <div class="lg:col-span-2 min-w-0 rounded-xl border border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Net income &amp; volume over time</h3>
                <div class="flex items-center gap-4 mt-1.5">
                    <span class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <span class="h-2 w-2 rounded-full bg-lime-500"></span> Net Income
                    </span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <span class="h-2 w-2 rounded-full bg-indigo-400"></span> Liters Sold
                    </span>
                </div>
            </div>
            <span class="text-slate-300 dark:text-slate-600 text-lg leading-none select-none">&#8943;</span>
        </div>
        <div class="p-4">
            <div class="relative h-[220px] sm:h-[260px]"><canvas id="performanceChart"></canvas></div>
        </div>
    </div>

    <div class="flex flex-col gap-5">
        <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white">Net income this month</h3>
                <span class="text-slate-300 dark:text-slate-600 text-lg leading-none select-none">&#8943;</span>
            </div>
            <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">₱{{ number_format($netIncomeKpi['current'], 0) }}</p>
            <p class="text-sm text-slate-400 dark:text-slate-500">{{ $netIncomeKpi['currentLabel'] }}</p>
            <div class="mt-3 space-y-1 text-sm">
                @if($netIncomeKpi['changePct'] !== null)
                    <p class="flex items-center gap-1.5 font-semibold {{ $netIncomeKpi['changePct'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        <span>{{ $netIncomeKpi['changePct'] >= 0 ? '↑' : '↓' }}</span>
                        {{ number_format(abs($netIncomeKpi['changePct']), 2) }}%
                        <span class="font-normal text-slate-500 dark:text-slate-400">vs. {{ $netIncomeKpi['previousLabel'] }}: ₱{{ number_format($netIncomeKpi['previous'], 2) }}</span>
                    </p>
                @else
                    <p class="text-slate-400 dark:text-slate-500">No prior month data to compare yet.</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white">Collection rate</h3>
                <span class="text-slate-300 dark:text-slate-600 text-lg leading-none select-none">&#8943;</span>
            </div>
            <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ number_format($customerSummary['collectionRate'], 1) }}%</p>
            <div class="mt-3 h-3 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500" style="width: {{ $customerSummary['collectionRate'] }}%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                <span>₱{{ number_format($customerSummary['totalPaid'], 0) }} collected</span>
                <span>Target ₱{{ number_format($customerSummary['totalPayables'], 0) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ===================== For the Customer ===================== --}}
<div class="mb-4">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white">For the Customer</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Remaining balance reflects
        <em class="not-italic font-semibold text-slate-700 dark:text-slate-300">payables less payments received</em>
        across all customer purchases on record.
    </p>
</div>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-10">
    <x-stat-card label="Total Liters" :value="number_format($customerSummary['totalLiters'], 2)" />
    <x-stat-card label="Total Payables" :value="'₱'.number_format($customerSummary['totalPayables'], 2)" />
    <x-stat-card label="Total Paid" :value="'₱'.number_format($customerSummary['totalPaid'], 2)" color="text-green-600 dark:text-green-400" />
    <x-stat-card label="Remaining Balance" :value="'₱'.number_format($customerSummary['remainingBalance'], 2)"
        sublabel="Total unpaid customer balance. Computed from payables less payments."
        color="text-red-600 dark:text-red-400" />
    <x-stat-card label="Net Income" :value="'₱'.number_format($customerSummary['netIncome'], 2)"
        sublabel="Computed as Payables minus Sub-total with Freight."
        color="text-amber-600 dark:text-amber-400" />
</div>

{{-- ===================== Deeper Dive ===================== --}}
<div class="mb-4">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Deeper Dive</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        @if(count($stocksRemaining['labels']))
            <em class="not-italic font-semibold text-slate-700 dark:text-slate-300">{{ $stocksRemaining['labels'][0] }}</em>
            holds the largest remaining stock; volume sold by product over the last 4 months is broken down on the right.
        @else
            No remaining stock recorded yet across supplier orders.
        @endif
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-10">
    <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-semibold text-slate-900 dark:text-white">Stocks remaining</h3>
            <span class="text-slate-300 dark:text-slate-600 text-lg leading-none select-none">&#8943;</span>
        </div>
        <div class="p-5 flex flex-col sm:flex-row items-center gap-6 overflow-hidden">
            <div class="relative h-40 w-40 shrink-0 mx-auto sm:mx-0">
                <canvas id="stockDoughnutChart" width="160" height="160" class="max-h-40 max-w-40"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <p class="text-xl font-extrabold text-slate-900 dark:text-white">{{ number_format($stocksRemaining['total'], 0) }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Liters</p>
                </div>
            </div>
            <ul class="w-full min-w-0 space-y-2 text-sm">
                @foreach($stocksRemaining['labels'] as $i => $label)
                    <li class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ ['#f59e0b','#6366f1','#22c55e','#a855f7','#ef4444','#0d9488','#64748b'][$i % 7] }}"></span>
                        <span class="text-slate-600 dark:text-slate-300 truncate">{{ $label }}</span>
                        <span class="ml-auto shrink-0 font-semibold text-slate-900 dark:text-white">{{ number_format($stocksRemaining['values'][$i], 0) }} L</span>
                    </li>
                @endforeach
                @if(!count($stocksRemaining['labels']))
                    <li class="text-slate-400 dark:text-slate-500">No remaining stock.</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Liters sold by product</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Last 4 months</p>
            </div>
            <span class="text-slate-300 dark:text-slate-600 text-lg leading-none select-none">&#8943;</span>
        </div>
        <div class="p-4">
            <div class="relative h-[220px] sm:h-[260px]"><canvas id="productBarChart"></canvas></div>
        </div>
    </div>
</div>

{{-- ===================== Recent activity ===================== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Recent Supplier Orders</h2>
            <a href="{{ route('fuel-sales-orders.index') }}" class="text-sm font-medium text-amber-600 hover:underline dark:text-amber-400">View all</a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 border-b border-slate-100 dark:text-slate-500 dark:border-slate-800">
                <tr><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Order No.</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Supplier</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Balance</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recentSalesOrders as $order)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap"><a href="{{ route('fuel-sales-orders.edit', $order) }}" class="font-medium text-amber-600 hover:underline dark:text-amber-400">{{ $order->sales_order_no }}</a></td>
                        <td class="px-4 sm:px-6 py-3 text-slate-600 whitespace-nowrap dark:text-slate-300">{{ $order->supplier }}</td>
                        <td class="px-4 sm:px-6 py-3 text-slate-600 whitespace-nowrap dark:text-slate-300">₱{{ number_format($order->balance_amount, 2) }}</td>
                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                            <x-badge :color="match($order->status) { 'paid' => 'green', 'partial' => 'yellow', default => 'red' }">{{ ucfirst($order->status) }}</x-badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">No supplier orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Recent Customer Purchases</h2>
            <a href="{{ route('fuel-customer-purchases.index') }}" class="text-sm font-medium text-amber-600 hover:underline dark:text-amber-400">View all</a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 border-b border-slate-100 dark:text-slate-500 dark:border-slate-800">
                <tr><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Customer</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Sales Order</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Net Income</th><th class="px-4 sm:px-6 py-3 font-medium whitespace-nowrap">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recentPurchases as $purchase)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap"><a href="{{ route('fuel-customer-purchases.edit', $purchase) }}" class="font-medium text-amber-600 hover:underline dark:text-amber-400">{{ $purchase->customer }}</a></td>
                        <td class="px-4 sm:px-6 py-3 text-slate-600 whitespace-nowrap dark:text-slate-300">{{ $purchase->sales_order_no }}</td>
                        <td class="px-4 sm:px-6 py-3 text-slate-600 whitespace-nowrap dark:text-slate-300">₱{{ number_format($purchase->net_income, 2) }}</td>
                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                            <x-badge :color="match($purchase->status) { 'paid' => 'green', 'partial' => 'yellow', 'overpaid' => 'blue', default => 'red' }">{{ ucfirst($purchase->status) }}</x-badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">No customer purchases yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lime = '#84cc16';
        const indigo = '#818cf8';
        const amber = '#f59e0b';
        const productPalette = ['#f59e0b', '#6366f1', '#22c55e', '#a855f7', '#ef4444', '#0d9488', '#64748b'];

        const isDark = () => document.documentElement.classList.contains('dark');
        const gridColor = () => isDark() ? 'rgba(148, 163, 184, 0.12)' : 'rgba(100, 116, 139, 0.1)';
        const tickColor = () => isDark() ? '#94a3b8' : '#64748b';
        const legendColor = () => isDark() ? '#cbd5e1' : '#475569';

        // Combo chart — net income (line) + liters sold (bar), dual axis
        const performanceChart = new Chart(document.getElementById('performanceChart'), {
            data: {
                labels: @json($performanceTrend['labels']),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Liters Sold',
                        data: @json($performanceTrend['liters']),
                        backgroundColor: indigo,
                        borderRadius: 6,
                        yAxisID: 'y1',
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'Net Income',
                        data: @json($performanceTrend['netIncome']),
                        borderColor: lime,
                        backgroundColor: lime,
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: lime,
                        yAxisID: 'y',
                        order: 1,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        position: 'left',
                        ticks: { color: tickColor(), callback: v => '₱' + (v / 1000).toFixed(0) + 'k' },
                        grid: { color: gridColor() },
                    },
                    y1: {
                        position: 'right',
                        ticks: { color: tickColor() },
                        grid: { drawOnChartArea: false },
                    },
                    x: { ticks: { color: tickColor() }, grid: { display: false } },
                }
            }
        });

        // Doughnut — stocks remaining by product
        const stockLabels = @json($stocksRemaining['labels']);
        const stockValues = @json($stocksRemaining['values']);

        const stockChart = new Chart(document.getElementById('stockDoughnutChart'), {
            type: 'doughnut',
            data: {
                labels: stockLabels.length ? stockLabels : ['No stock data'],
                datasets: [{
                    data: stockValues.length ? stockValues : [1],
                    backgroundColor: stockLabels.length ? productPalette.slice(0, stockLabels.length) : ['#e2e8f0'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false } },
            }
        });

        // Stacked bar — liters sold by product, last 4 months
        const products = @json($volumeByProduct['products']);
        const series = @json($volumeByProduct['series']);

        const productChart = new Chart(document.getElementById('productBarChart'), {
            type: 'bar',
            data: {
                labels: @json($volumeByProduct['labels']),
                datasets: products.map((product, i) => ({
                    label: product,
                    data: series[i],
                    backgroundColor: productPalette[i % productPalette.length],
                    borderRadius: 4,
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: legendColor(), boxWidth: 10, boxHeight: 10 } } },
                scales: {
                    x: { stacked: true, ticks: { color: tickColor() }, grid: { display: false } },
                    y: { stacked: true, ticks: { color: tickColor() }, grid: { color: gridColor() } },
                }
            }
        });

        // Re-theme charts when dark mode toggles
        const charts = [performanceChart, productChart];
        const observer = new MutationObserver(() => {
            charts.forEach(chart => {
                if (chart.options.scales?.y) {
                    chart.options.scales.y.ticks.color = tickColor();
                    if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor();
                }
                if (chart.options.scales?.y1) {
                    chart.options.scales.y1.ticks.color = tickColor();
                }
                if (chart.options.scales?.x) {
                    chart.options.scales.x.ticks.color = tickColor();
                }
                if (chart.options.plugins?.legend?.labels) {
                    chart.options.plugins.legend.labels.color = legendColor();
                }
                chart.update();
            });
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
</script>
@endpush
