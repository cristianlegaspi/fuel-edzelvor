@extends('layouts.fuel')

@section('title', 'Customer Purchases')
@section('page-title', 'Customer Purchases')
@section('page-subtitle', 'Fuel resold to customers')

@section('header-actions')
    <a href="{{ route('fuel-customer-purchases.create') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">+ New Purchase</a>
@endsection

@section('content')
@php
    // Eager-load histories for only the purchases on this paginated page.
    $purchases->getCollection()->load('payments');
@endphp
<style>
    .customer-action {display:inline-flex;align-items:center;justify-content:center;gap:6px;border-radius:8px;padding:8px 12px;font-size:12px;font-weight:700;color:#fff!important;white-space:nowrap;transition:background-color .18s,transform .18s,box-shadow .18s}
    .customer-action:hover {transform:translateY(-2px);box-shadow:0 5px 14px rgba(0,0,0,.24)}
    .customer-action:focus-visible {outline:2px solid #fff;outline-offset:2px}
    .customer-action--edit {background:#d97706}.customer-action--edit:hover{background:#f59e0b}
    .customer-action--pay {background:#059669}.customer-action--pay:hover{background:#10b981}
    .customer-action--delete {background:#dc2626}.customer-action--delete:hover{background:#ef4444}
    .customer-action--view {background:#2563eb}.customer-action--view:hover{background:#3b82f6}
    .customer-action--locked {background:#334155;color:#cbd5e1!important;cursor:not-allowed}
    .customer-action--locked:hover{transform:none;box-shadow:none}
    /* Responsive payment dialog and animated darkened dashboard. */
    #customerPaymentModal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9000 !important;
        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;
        box-sizing: border-box;
        padding: 16px !important;
        background: rgba(2, 6, 23, .78) !important;
        -webkit-backdrop-filter: blur(3px);
        backdrop-filter: blur(3px);
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .24s ease, visibility .24s ease;
    }
    /* Use the .is-open state instead of relying on Tailwind display utilities. */
    #customerPaymentModal.is-open {
        display: flex !important;
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    #customerPaymentPanel {
        box-sizing: border-box;
        width: min(860px, 100%) !important;
        max-width: 860px !important;
        max-height: min(85vh, 780px) !important;
        max-height: min(85dvh, 780px) !important;
        min-width: 0;
        min-height: 0;
        margin: auto !important;
        transform: translateY(18px) scale(.975);
        opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .24s ease;
        box-shadow: 0 30px 80px rgba(0,0,0,.5);
    }
    #customerPaymentModal.is-open #customerPaymentPanel {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    #customerPaymentPanel input, #customerPaymentPanel select {box-sizing:border-box;max-width:100%;min-width:0}
    #customerPaymentPanel .payment-dialog-scroll {scrollbar-width:thin;scrollbar-color:#64748b transparent}
    @media (max-width: 640px) {
        #customerPaymentModal { padding: 10px !important; }
        #customerPaymentPanel {
            width: 100% !important;
            max-height: calc(100dvh - 20px) !important;
            border-radius: 14px;
        }
    }
    /* Strong colors for payment summary and actions, including when Tailwind
       content scanning has not generated newly added classes. */
    /* Always show the 3 summary cards in one row, even on mobile. */
    .payment-summary-row {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;width:100%}
    .payment-summary-row > * {min-width:0}
    .payment-summary-card {border-radius:12px;padding:12px;border:1px solid transparent;box-shadow:0 5px 16px rgba(0,0,0,.08);min-width:0}
    .payment-summary-card .payment-summary-amount {overflow-wrap:anywhere}
    @media(max-width:640px) {
        .payment-summary-row {gap:6px}
        .payment-summary-card {padding:8px 6px;border-radius:8px}
        .payment-summary-card .payment-summary-label {font-size:10px}
        .payment-summary-card .payment-summary-amount {font-size:clamp(11px,2.7vw,15px);line-height:1.3}
    }
    .payment-summary-card--payables {background:#eff6ff;border-color:#bfdbfe;color:#1e40af}
    .payment-summary-card--paid {background:#ecfdf5;border-color:#a7f3d0;color:#047857}
    .payment-summary-card--balance {background:#fffbeb;border-color:#fde68a;color:#b45309}
    .payment-summary-card .payment-summary-label {font-size:12px;font-weight:600;opacity:.8}
    .payment-summary-card .payment-summary-amount {font-size:18px;font-weight:800;margin-top:5px;font-variant-numeric:tabular-nums}
    .dark .payment-summary-card--payables {background:#172b4e;border-color:#315892;color:#bfdbfe}
    .dark .payment-summary-card--paid {background:#083b32;border-color:#047857;color:#a7f3d0}
    .dark .payment-summary-card--balance {background:#443115;border-color:#92601d;color:#fde68a}
    #customerPaymentSubmit {background:#059669!important;color:#fff!important;border:1px solid #047857;box-shadow:0 3px 12px rgba(5,150,105,.25);transition:background-color .2s,transform .2s,box-shadow .2s}
    #customerPaymentSubmit:hover:not(:disabled) {background:#047857!important;transform:translateY(-1px);box-shadow:0 6px 18px rgba(5,150,105,.35)}
    #customerPaymentSubmit:disabled {opacity:.65;cursor:wait}
    /* Visible validation alert: not dependent on Tailwind compilation. */
    #customerProofError {display:none;align-items:flex-start;gap:10px;padding:12px 14px;margin-top:10px;background:#fef2f2!important;border:1px solid #ef4444!important;border-left:5px solid #dc2626!important;border-radius:10px;color:#b91c1c!important;font-size:13px;font-weight:700;line-height:1.5;box-shadow:0 3px 12px rgba(220,38,38,.10)}
    #customerProofError:not(.hidden) {display:flex!important}
    .dark #customerProofError {background:#450a0a!important;border-color:#f87171!important;color:#fecaca!important}
    #customerProofErrorIcon {font-size:18px;line-height:1.2;flex:none}
    #customerReferenceError, #customerPaymentServerErrors {display:none;align-items:flex-start;gap:9px;padding:10px 12px;margin-top:8px;background:#fef2f2!important;border:1px solid #ef4444!important;border-left:4px solid #dc2626!important;border-radius:9px;color:#b91c1c!important;font-size:12px;font-weight:700;line-height:1.5;overflow-wrap:anywhere}
    #customerReferenceError:not(.hidden), #customerPaymentServerErrors:not(.hidden) {display:flex!important}
    .dark #customerReferenceError, .dark #customerPaymentServerErrors {background:#450a0a!important;border-color:#f87171!important;color:#fecaca!important}
    #customerReferenceNo.customer-input-invalid {border:2px solid #ef4444!important;outline:none}

    #customerProofSelected {background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:10px;flex-wrap:wrap}
    #customerProofThumb {display:block;width:64px;height:64px;object-fit:cover;border:1px solid #94a3b8;border-radius:8px;background:#f8fafc;color:#475569}
    .dark #customerProofThumb {background:#1e293b;color:#cbd5e1}
    #customerProofPreview {background:#d97706;color:white;border-radius:7px;padding:7px 10px;font-size:12px;font-weight:700}
    #customerProofPreview:hover {background:#b45309}
    #customerProofRemove {background:#dc2626;color:white;border-radius:7px;padding:7px 10px;font-size:12px;font-weight:700}
    #customerProofRemove:hover {background:#b91c1c}
    @media (prefers-reduced-motion: reduce) {
        #customerPaymentModal, #customerPaymentPanel {transition:none !important}
    }

    /* Compact desktop/tablet table; never push dashboard past viewport width. */
    .customer-table-shell {width:100%;min-width:0;overflow:hidden}
    .customer-table-scroll {width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .customer-purchases-table {width:100%;border-collapse:collapse;table-layout:auto}
    .customer-purchases-table th {padding:12px 10px;white-space:nowrap;font-size:12px}
    .customer-purchases-table td {padding:11px 10px;font-size:12px;vertical-align:middle}
    .customer-purchases-table td.customer-col {min-width:145px;max-width:225px;white-space:normal;overflow-wrap:anywhere}
    .customer-purchases-table td.customer-actions-col {min-width:190px;text-align:right}
    .customer-table-actions {display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:6px}
    .customer-table-actions .customer-action {font-size:11px;padding:7px 9px;min-height:32px}
    @media (min-width:701px) and (max-width:1199px) {
        .customer-purchases-table {min-width:880px}
    }
    @media (max-width:700px) {
        .customer-table-shell {border:0!important;background:transparent!important;overflow:visible!important}
        .customer-table-scroll {overflow:visible!important}
        .customer-purchases-table,.customer-purchases-table tbody {display:block;width:100%}
        .customer-purchases-table thead {display:none}
        .customer-purchases-table tr.customer-record-row {display:block;margin:0 0 12px;padding:8px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 4px 12px rgba(15,23,42,.04)}
        .dark .customer-purchases-table tr.customer-record-row {background:#111a2e;border-color:#334155}
        .customer-purchases-table tr.customer-record-row td {display:flex;align-items:flex-start;justify-content:space-between;gap:12px;width:100%;min-width:0;max-width:none!important;padding:9px 0!important;border-bottom:1px solid #e2e8f0;text-align:right;white-space:normal!important;overflow-wrap:anywhere}
        .dark .customer-purchases-table tr.customer-record-row td {border-bottom-color:#26354b}
        .customer-purchases-table tr.customer-record-row td:last-child {border-bottom:0}
        .customer-purchases-table tr.customer-record-row td::before {content:attr(data-label);flex:0 0 35%;text-align:left;font-size:11px;font-weight:700;color:#64748b}
        .dark .customer-purchases-table tr.customer-record-row td::before {color:#94a3b8}
        .customer-purchases-table tr.customer-record-row td.customer-actions-col {display:block;text-align:left}
        .customer-purchases-table tr.customer-record-row td.customer-actions-col::before {display:block;margin-bottom:9px;content:'Actions'}
        .customer-table-actions {justify-content:flex-start;gap:7px}
        .customer-table-actions .customer-action {font-size:11px;padding:9px 11px}
        .customer-purchases-table tr.customer-empty-row {display:block}
        .customer-purchases-table tr.customer-empty-row td {display:block;text-align:center;padding:28px 14px}
    }

 .customer-proof-blue{display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;background:#2563eb!important;color:#fff!important;border-radius:7px;padding:7px 10px;font-weight:700;font-size:11px;transition:background .2s,transform .2s}
 .customer-proof-blue:hover{background:#1d4ed8!important;transform:translateY(-1px)}
 /* Customer payment correction and audit history */
.customer-history-action{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:7px;padding:6px 9px;font-size:11px;font-weight:700;color:#fff!important;cursor:pointer;white-space:nowrap}
.customer-history-action--edit{background:#d97706}.customer-history-action--edit:hover{background:#b45309}
.customer-history-action--audit{background:#475569}.customer-history-action--audit:hover{background:#334155}
.customer-history-buttons{display:flex;align-items:center;flex-wrap:wrap;gap:5px}
.customer-edit-overlay{position:fixed;inset:0;z-index:9200;background:rgba(2,6,23,.8);backdrop-filter:blur(3px);padding:12px;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s,visibility .2s}
.customer-edit-overlay.is-open{opacity:1;visibility:visible;pointer-events:auto}
.customer-edit-panel{display:flex;flex-direction:column;width:min(670px,100%);max-height:90dvh;overflow:hidden;border-radius:15px;border:1px solid #475569;background:#0f172a;color:#e2e8f0;transform:scale(.97) translateY(10px);transition:transform .2s;box-shadow:0 25px 60px #0007}
.customer-edit-overlay.is-open .customer-edit-panel{transform:scale(1) translateY(0)}
.customer-edit-field{width:100%;min-width:0;padding:10px;border-radius:8px;border:1px solid #475569;background:#1e293b;color:white;font-size:13px}
.customer-edit-field:focus{outline:2px solid #38bdf8;outline-offset:1px}
.customer-audit-table{width:100%;border-collapse:collapse;font-size:12px}.customer-audit-table td,.customer-audit-table th{padding:8px;text-align:left;border-bottom:1px solid #334155;vertical-align:top;overflow-wrap:anywhere}
@media(max-width:640px){.customer-edit-overlay{padding:7px}.customer-edit-panel{max-height:95dvh}.customer-history-buttons{justify-content:flex-start}}
</style>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-500" role="status">{{ session('success') }}</div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer or SO no..." class="w-full sm:w-72 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <option value="">All statuses</option>
        @foreach(['unpaid','partial','paid','overpaid'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="customer-table-shell rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="customer-table-scroll overflow-x-auto">
        <table class="customer-purchases-table w-full text-sm">
            <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Customer</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Sales Order</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Total Liters</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Payables</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Net Income</th>
                    <th class="px-6 py-3 font-medium whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($purchases as $purchase)
                    @php
                        $status = strtolower(trim((string) $purchase->status));
                        $locked = in_array($status, ['paid', 'overpaid'], true);
                        $paid = $status === 'paid';
                        $payments = $purchase->payments->sortByDesc('id');
                        $totalPaid = (float) $payments->sum('amount');
                        $balance = max(0, (float) $purchase->total_payables - $totalPaid);
                    @endphp
                    <tr class="customer-record-row hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <td data-label="Customer" class="customer-col px-6 py-3 whitespace-nowrap font-medium">
                            @if($paid)
                                <span class="text-slate-700 dark:text-slate-200">{{ $purchase->customer }}</span>
                            @else
                                <a href="{{ route('fuel-customer-purchases.edit', $purchase) }}" class="text-amber-600 dark:text-amber-400 hover:underline">{{ $purchase->customer }}</a>
                            @endif
                        </td>
                        <td data-label="Sales Order" class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $purchase->sales_order_no }}</td>
                        <td data-label="Total Liters" class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ number_format($purchase->total_liters, 2) }} L</td>
                        <td data-label="Payables" class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($purchase->total_payables, 2) }}</td>
                        <td data-label="Net Income" class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">₱{{ number_format($purchase->net_income, 2) }}</td>
                        <td data-label="Status" class="px-6 py-3 whitespace-nowrap">
                            <x-badge :color="match($status) { 'paid'=>'green','partial'=>'yellow','overpaid'=>'blue',default=>'red' }">{{ ucfirst($status) }}</x-badge>
                        </td>
                        <td data-label="Actions" class="customer-actions-col px-4 py-3 whitespace-nowrap text-right">
                            <div class="customer-table-actions">
                                @if($paid)
                                    <a class="customer-action customer-action--view" href="{{ route('fuel-customer-purchases.details', $purchase) }}">◉ View Details</a>
                                @else
                                    <a class="customer-action customer-action--edit" href="{{ route('fuel-customer-purchases.edit', $purchase) }}">✎ Edit</a>
                                @endif

                                {{-- Unified history + payment modal. Paid/overpaid are view-only. --}}
                                <button type="button" class="customer-action {{ $locked ? 'customer-action--view' : 'customer-action--pay' }}"
                                        data-payment-open
                                        data-purchase-id="{{ $purchase->getKey() }}"
                                        data-customer="{{ $purchase->customer }}"
                                        data-order="{{ $purchase->sales_order_no }}"
                                        data-status="{{ $status }}"
                                        data-payables="{{ number_format((float) $purchase->total_payables, 2) }}"
                                        data-paid="{{ number_format($totalPaid, 2) }}"
                                        data-balance="{{ number_format($balance, 2) }}"
                                        data-action="{{ route('fuel-customer-purchases.payments.store', $purchase) }}">
                                    {{ $locked ? '▣ View Payments' : '▣ Add Payment' }}
                                </button>

                                @if($paid)
                                    <span class="customer-action customer-action--locked">♙ Locked</span>
                                @else
                                    <form action="{{ route('fuel-customer-purchases.destroy', $purchase) }}" method="POST" class="inline-flex"
                                          data-confirm="This will permanently delete the customer purchase and its items. This cannot be undone."
                                          data-confirm-type="danger" data-confirm-title="Delete this purchase?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="customer-action customer-action--delete">▤ Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    {{-- Page-specific payment history template. No additional route or AJAX request required. --}}
                    <template id="customer-payment-history-{{ $purchase->getKey() }}">
                        @forelse($payments as $payment)
                            <tr class="border-b border-slate-200 dark:border-slate-800">
                                <td class="px-3 py-3 whitespace-nowrap">{{ $payment->customer_payment_tracking_no ?: '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('M d, Y') : '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap font-semibold">₱{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-3 py-3">{{ $payment->payment_method ?: '—' }}</td>
                                <td class="px-3 py-3">{{ $payment->reference_no ?: '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if($payment->proof_image)
                                        <button type="button" class="customer-proof-blue"
                                                data-proof-url="{{ route('fuel-customer-payments.proof', $payment) }}">View Proof</button>
                                    @else
                                        <span class="text-slate-400">No image</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="customer-history-buttons">
                                        @if(auth()->user()?->isAdminOrAbove())
                                            <button type="button" class="customer-history-action customer-history-action--edit"
                                                data-payment-edit
                                                data-url="{{ route('fuel-customer-payments.update', $payment) }}"
                                                data-tracking="{{ $payment->customer_payment_tracking_no }}"
                                                data-date="{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('Y-m-d') : '' }}"
                                                data-amount="{{ $payment->amount }}"
                                                data-edit-max="{{ number_format(max(0,(float)$purchase->total_payables - ($totalPaid - (float)$payment->amount)), 2, '.', '') }}"
                                                data-method="{{ $payment->payment_method }}"
                                                data-reference="{{ $payment->reference_no }}"
                                                data-remarks="{{ $payment->remarks }}"
                                                data-proof="{{ $payment->proof_image ? route('fuel-customer-payments.proof', $payment) : '' }}">✎ Edit</button>
                                        @endif
                                        <button type="button" class="customer-history-action customer-history-action--audit"
                                            data-payment-audit="{{ route('fuel-customer-payments.history', $payment) }}"
                                            data-tracking="{{ $payment->customer_payment_tracking_no }}">History</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-8 text-center text-slate-400">No payments recorded yet.</td></tr>
                        @endforelse
                    </template>
                @empty
                    <tr class="customer-empty-row"><td colspan="7" class="px-6 py-10 text-center text-slate-400">No customer purchases found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6"><x-pagination :paginator="$purchases" /></div>

{{-- Unified payment history and add-payment modal --}}
<div id="customerPaymentModal" class="fixed inset-0" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="customerPaymentModalTitle">
    <div id="customerPaymentPanel" class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-700 bg-white dark:bg-slate-900 shadow-2xl">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 dark:border-slate-700 px-5 py-4">
            <div>
                <h2 id="customerPaymentModalTitle" class="text-lg font-bold text-slate-900 dark:text-white">Customer Payments</h2>
                <p id="customerPaymentModalSubtitle" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
            </div>
            <button type="button" id="customerPaymentClose" class="rounded-lg px-2 py-1 text-xl text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800" aria-label="Close">&times;</button>
        </div>
        <div class="payment-dialog-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 space-y-5">
            <div class="payment-summary-row">
                <div class="payment-summary-card payment-summary-card--payables"><div class="payment-summary-label">Total Payables</div><div id="customerPaymentPayables" class="payment-summary-amount"></div></div>
                <div class="payment-summary-card payment-summary-card--paid"><div class="payment-summary-label">Total Paid</div><div id="customerPaymentPaid" class="payment-summary-amount"></div></div>
                <div class="payment-summary-card payment-summary-card--balance"><div class="payment-summary-label">Remaining Balance</div><div id="customerPaymentBalance" class="payment-summary-amount"></div></div>
            </div>
            <section>
                <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Payment History</h3>
                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="min-w-[750px] w-full text-xs text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-100 dark:bg-slate-800 text-left text-slate-500 dark:text-slate-400">
                            <tr><th class="px-3 py-3">Tracking No.</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Method</th><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Proof</th><th class="px-3 py-3">Actions</th></tr>
                        </thead>
                        <tbody id="customerPaymentHistoryBody"></tbody>
                    </table>
                </div>
            </section>
            <div id="customerPaymentLocked" class="hidden rounded-lg border border-slate-600 bg-slate-100 dark:bg-slate-800 px-4 py-3 text-sm text-slate-600 dark:text-slate-300">This purchase is paid or overpaid. Payment history is view-only; additional payments are disabled.</div>
            <section id="customerPaymentFormSection">
                <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Add Customer Payment</h3>
                <form id="customerPaymentForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div id="customerPaymentServerErrors" class="hidden" role="alert" aria-live="assertive"><span aria-hidden="true">⚠</span><span id="customerPaymentServerErrorsMessage"></span></div>
                    <input type="hidden" id="customerMaxAdditional" value="0">
                    <input type="hidden" name="payment_purchase_id" id="customerPaymentPurchaseId">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Payment Date
                            <input name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Amount
                            <input id="customerAddAmount" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Payment Method
                            <select name="payment_method" required aria-required="true" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                                <option value="">Select Method</option>
                                @foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Reference No. <span class="text-red-500">*</span>
                            <input id="customerReferenceNo" name="reference_no" type="text" required maxlength="255" aria-describedby="customerReferenceError" value="{{ old('reference_no') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                            <span id="customerReferenceError" class="hidden" role="alert" aria-live="polite"><span aria-hidden="true">⚠</span><span id="customerReferenceErrorMessage"></span></span>
                        </label>
                    </div>
                    <div>
                        <label for="customerProofInput" class="mb-1 block text-xs text-slate-500 dark:text-slate-400">Proof of Payment (image only)</label>
                        <input id="customerProofInput" name="proof_image" type="file" required aria-required="true" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 p-2 text-sm">
                        <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP, GIF · Maximum 5 MB</p>
                        <div id="customerProofSelected" class="mt-3 hidden items-center gap-3">
                            <img id="customerProofThumb" class="h-16 w-16 rounded border object-cover" alt="Selected proof thumbnail">
                            <button type="button" id="customerProofPreview" class="text-sm font-semibold">Preview Image</button>
                            <button type="button" id="customerProofRemove" class="text-sm font-semibold">Remove</button>
                        </div>
                        <div id="customerProofError" class="hidden" role="alert" aria-live="assertive"><span id="customerProofErrorIcon" aria-hidden="true">⚠</span><span id="customerProofErrorMessage"></span></div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-700 pt-4">
                        <button type="button" id="customerPaymentCancel" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                        <button id="customerPaymentSubmit" type="submit" class="rounded-lg px-6 py-2.5 text-sm font-bold text-white">Save Payment</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>


{{-- Payment corrections and history: outside financial modal for proper layering. --}}
@if(auth()->user()?->isAdminOrAbove())
<div id="customerEditPaymentOverlay" class="customer-edit-overlay" role="dialog" aria-modal="true" aria-labelledby="customerEditPaymentTitle" aria-hidden="true">
 <div class="customer-edit-panel">
  <header class="flex items-center justify-between border-b border-slate-700 p-4">
    <div><h3 id="customerEditPaymentTitle" class="font-bold">Edit Customer Payment</h3><p id="customerEditTracking" class="mt-1 text-xs text-slate-400"></p></div>
    <button id="customerEditClose" type="button" class="rounded p-2 text-xl" aria-label="Close">&times;</button>
  </header>
  <div class="min-h-0 flex-1 overflow-y-auto p-4">
    <p class="mb-3 rounded-lg border border-amber-600/50 bg-amber-900/20 p-3 text-xs text-amber-200">Financial correction: changing the amount may recalculate purchase totals. All changes and the reason are recorded in Audit Logs.</p>
    <form id="customerEditPaymentForm" method="POST" enctype="multipart/form-data" class="space-y-3">@csrf @method('PATCH')
      <input type="hidden" name="payment_purchase_id" id="customerEditPurchaseId">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label class="text-xs">Payment Date<input id="customerEditDate" name="payment_date" type="date" class="customer-edit-field mt-1"></label>
        <label class="text-xs">Amount (₱)<input id="customerEditAmount" name="amount" type="number" required min="0.01" step="0.01" class="customer-edit-field mt-1"><span id="customerEditMaxHelp" class="mt-1 block text-emerald-300"></span></label>
        <label class="text-xs">Payment Method<select id="customerEditMethod" name="payment_method" class="customer-edit-field mt-1"><option value="">Select Method</option>@foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $method)<option value="{{ $method }}">{{ $method }}</option>@endforeach</select></label>
        <label class="text-xs">Reference No.<input id="customerEditReference" name="reference_no" type="text" class="customer-edit-field mt-1"></label>
      </div>
      <label class="block text-xs">Remarks<textarea id="customerEditRemarks" name="remarks" rows="2" class="customer-edit-field mt-1"></textarea></label>
      <label class="block text-xs">Reason for Editing <span class="text-red-400">*</span><textarea id="customerEditReason" name="edit_reason" required minlength="10" maxlength="1000" rows="2" placeholder="Explain what was incorrect and why it must be corrected..." class="customer-edit-field mt-1"></textarea></label>
      <label class="block text-xs">Replace Receipt (optional)<input id="customerEditProofInput" name="proof_image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="customer-edit-field mt-1"><span class="mt-1 block text-slate-400">JPG, PNG, WEBP, GIF · Maximum 5 MB. Existing proof remains unless replaced.</span></label>
      <p id="customerEditReceiptWarning" class="hidden rounded-lg border border-red-500 bg-red-950/50 p-3 text-sm font-semibold text-red-300" role="alert"></p>
      <div id="customerEditOriginalProof" class="hidden text-xs"><button type="button" id="customerEditOriginalButton" class="text-sky-400 hover:underline">View existing receipt</button></div>
      <footer class="flex justify-end gap-2 border-t border-slate-700 pt-3"><button type="button" id="customerEditCancel" class="rounded-lg border border-slate-600 px-4 py-2 text-sm">Cancel</button>
<button
    type="submit"
    id="customerEditSave"
    style="background-color: #059669 !important; color: #ffffff !important;"
    class="rounded-lg px-6 py-2.5 text-sm font-bold
           shadow-md transition-all duration-200
           hover:brightness-110 hover:shadow-lg
           disabled:cursor-not-allowed disabled:opacity-60"
>
    Save Correction
</button>
</footer>
    </form>
  </div>
 </div>
</div>
@endif
<div id="customerAuditOverlay" class="customer-edit-overlay" role="dialog" aria-modal="true" aria-labelledby="customerAuditTitle" aria-hidden="true">
 <div class="customer-edit-panel">
  <header class="flex items-center justify-between border-b border-slate-700 p-4"><div><h3 id="customerAuditTitle" class="font-bold">Customer Payment Edit History</h3><p id="customerAuditTracking" class="mt-1 text-xs text-slate-400"></p></div><button type="button" id="customerAuditClose" class="p-2 text-xl" aria-label="Close">&times;</button></header>
  <div class="min-h-0 flex-1 overflow-y-auto p-4"><div id="customerAuditContent" class="text-sm">Loading…</div></div>
 </div>
</div>

{{-- Your existing draggable / resizable / wheel-zoom image modal --}}
@include('fuel.partials.payment-proof-modal')

<script>
(function () {
    const modal = document.getElementById('customerPaymentModal');
    // Layout wrappers may use transform/overflow, which breaks fixed-position
    // modals. Move both overlays directly under <body> without duplicating them.
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    const proofModal = document.getElementById('paymentProofModal');
    if (proofModal && proofModal.parentElement !== document.body) {
        document.body.appendChild(proofModal);
    }
    const form = document.getElementById('customerPaymentForm');
    const historyBody = document.getElementById('customerPaymentHistoryBody');
    const fileInput = document.getElementById('customerProofInput');
    const selected = document.getElementById('customerProofSelected');
    const thumb = document.getElementById('customerProofThumb');
    const fileError = document.getElementById('customerProofError');
    const fileErrorMessage = document.getElementById('customerProofErrorMessage');
    const referenceInput = document.getElementById('customerReferenceNo');
    const referenceError = document.getElementById('customerReferenceError');
    const referenceErrorMessage = document.getElementById('customerReferenceErrorMessage');
    const serverErrors = document.getElementById('customerPaymentServerErrors');
    const serverErrorsMessage = document.getElementById('customerPaymentServerErrorsMessage');
    function showReferenceError(message) { referenceErrorMessage.textContent = message; referenceError.classList.remove('hidden'); referenceInput.classList.add('customer-input-invalid'); referenceInput.setAttribute('aria-invalid', 'true'); }
    function hideReferenceError() { referenceErrorMessage.textContent = ''; referenceError.classList.add('hidden'); referenceInput.classList.remove('customer-input-invalid'); referenceInput.removeAttribute('aria-invalid'); }
    function showServerErrors(message) { serverErrorsMessage.textContent = message; serverErrors.classList.remove('hidden'); }
    function hideServerErrors() { serverErrorsMessage.textContent = ''; serverErrors.classList.add('hidden'); }
    form.elements['payment_method'].addEventListener('change', hideServerErrors);
    form.elements['payment_method'].addEventListener('invalid', event => {
        event.preventDefault();
        showServerErrors('Payment Method is required. Please select a payment method.');
    });
    referenceInput.addEventListener('input', hideReferenceError);
    referenceInput.addEventListener('invalid', e => { if (!referenceInput.value.trim()) { e.preventDefault(); showReferenceError('Reference No. is required.'); } });
    function showProofError(message) { fileErrorMessage.textContent = message; fileError.classList.remove('hidden'); }
    function hideProofError() { fileErrorMessage.textContent = ''; fileError.classList.add('hidden'); }
    const formSection = document.getElementById('customerPaymentFormSection');
    const lockedMessage = document.getElementById('customerPaymentLocked');
    let previewUrl = null;
    let previousOverflow = '';
    let lastTrigger = null;

    function clearFile() {
        fileReadToken++;
        fileInput.value = '';
        previewUrl = null;
        thumb.removeAttribute('src');
        selected.classList.add('hidden');
        selected.classList.remove('flex');
        hideProofError();
    }
    function closeModal() {
        if (!modal.classList.contains('is-open')) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousOverflow;
        clearFile();
        lastTrigger?.focus();
    }
    function openModal(button) {
        lastTrigger = button;
        const id = button.dataset.purchaseId;
        const status = button.dataset.status;
        const readonly = status === 'paid' || status === 'overpaid';
        const history = document.getElementById('customer-payment-history-' + id);
        historyBody.replaceChildren();
        if (history) historyBody.appendChild(history.content.cloneNode(true));
        form.reset();
        clearFile();
        hideReferenceError();
        hideServerErrors();
        form.action = button.dataset.action;
        document.getElementById('customerPaymentPurchaseId').value = id;
        document.getElementById('customerPaymentModalSubtitle').textContent = button.dataset.customer + ' · ' + button.dataset.order;
        document.getElementById('customerPaymentPayables').textContent = '₱' + button.dataset.payables;
        document.getElementById('customerPaymentPaid').textContent = '₱' + button.dataset.paid;
        document.getElementById('customerPaymentBalance').textContent = '₱' + button.dataset.balance;
        document.getElementById('customerMaxAdditional').value = button.dataset.balance.replace(/,/g,'');
        formSection.classList.toggle('hidden', readonly);
        lockedMessage.classList.toggle('hidden', !readonly);
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        modal.classList.add('is-open');
        modal.removeAttribute('aria-hidden');
        document.getElementById('customerPaymentClose').focus();
    }
    document.querySelectorAll('[data-payment-open]').forEach(button => {
        button.addEventListener('click', () => openModal(button));
    });
    document.getElementById('customerPaymentClose').addEventListener('click', closeModal);
    document.getElementById('customerPaymentCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.classList.contains('is-open') &&
            document.getElementById('paymentProofModal')?.classList.contains('hidden')) closeModal();
    });
    historyBody.addEventListener('click', e => {
        const button = e.target.closest('[data-proof-url]');
        if (button && typeof window.openPaymentProof === 'function') {
            window.openPaymentProof(button.dataset.proofUrl);
        }
    });
    // Use FileReader instead of a temporary blob URL. The thumbnail and
    // existing draggable modal can both display the same data URL.
    let fileReadToken = 0;
    fileInput.addEventListener('change', () => {
        const token = ++fileReadToken;
        previewUrl = null;
        thumb.removeAttribute('src');
        hideProofError();
        selected.classList.add('hidden');
        selected.classList.remove('flex');

        const file = fileInput.files?.[0];
        if (!file) return;
        const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        if (!allowedMimeTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
            showProofError('Invalid file format! Only JPG, PNG, WEBP, and GIF images are accepted. PDF, Word, Excel, and other files are not allowed.');
            fileInput.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showProofError('File too large! Maximum allowed image size is 5 MB. Please select a smaller JPG, PNG, WEBP, or GIF image.');
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            if (token !== fileReadToken) return;
            previewUrl = String(reader.result || '');
            thumb.src = previewUrl;
            selected.classList.remove('hidden');
            selected.classList.add('flex');
        };
        reader.onerror = () => {
            if (token !== fileReadToken) return;
            showProofError('Cannot read the selected image. Please choose another file.');
            fileInput.value = '';
        };
        reader.readAsDataURL(file);
    });
    thumb.addEventListener('error', () => {
        if (!fileInput.files?.length) return;
        showProofError('Image preview is blocked or this file cannot be displayed. Check the browser Content Security Policy (img-src data:).');
    });
    document.getElementById('customerProofPreview').addEventListener('click', () => {
        if (previewUrl && typeof window.openPaymentProof === 'function') window.openPaymentProof(previewUrl);
    });
    document.getElementById('customerProofRemove').addEventListener('click', clearFile);
    fileInput.addEventListener('invalid', event => {
        if (!fileInput.files?.length) {
            event.preventDefault();
            showProofError('Receipt is required! Please attach a JPG, PNG, WEBP, or GIF image (maximum 5 MB) before saving.');
        }
    });
    const addAmount = document.getElementById('customerAddAmount');
    function checkAddBalance(show) {
        const cents = Math.round(Number(addAmount.value) * 100);
        const maxCents = Math.round(Number(document.getElementById('customerMaxAdditional').value) * 100);
        if (!Number.isFinite(cents) || cents < 1) return true; // Native required/min validates.
        if (cents > maxCents) {
            if (show) showServerErrors('Payment exceeds the remaining balance. Maximum additional payment: ₱' + (maxCents/100).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}) + '.');
            return false;
        }
        return true;
    }
    addAmount.addEventListener('input', () => {hideServerErrors();checkAddBalance(true)});
    form.addEventListener('submit', e => {
        const id = document.getElementById('customerPaymentPurchaseId').value;
        const trigger = [...document.querySelectorAll('[data-payment-open]')].find(b => b.dataset.purchaseId === id);
        if (!trigger || ['paid', 'overpaid'].includes(trigger.dataset.status)) {
            e.preventDefault();
            return;
        }
        if (!referenceInput.value.trim()) {
            e.preventDefault();
            showReferenceError('Reference No. is required.');
            referenceInput.focus();
            return;
        }
        if (!form.elements['payment_method'].value) {
            e.preventDefault();
            showServerErrors('Payment Method is required. Please select a payment method.');
            form.elements['payment_method'].focus();
            return;
        }
        hideServerErrors();
        const receipt = fileInput.files?.[0];
        if (!receipt) {
            e.preventDefault();
            showProofError('Receipt is required! Please attach a JPG, PNG, WEBP, or GIF image (maximum 5 MB) before saving.');
            fileInput.focus();
            return;
        }
        const extension = (receipt.name.split('.').pop() || '').toLowerCase();
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(receipt.type)
            || !['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) {
            e.preventDefault();
            showProofError('Invalid file format! Only JPG, PNG, WEBP, and GIF images are accepted.');
            fileInput.value = '';
            return;
        }
        if (receipt.size > 5 * 1024 * 1024) {
            e.preventDefault();
            showProofError('File is too large! Maximum receipt size is 5 MB.');
            fileInput.value = '';
            return;
        }
        if (!checkAddBalance(true)) { e.preventDefault(); addAmount.focus(); return; }
        hideProofError();
        document.getElementById('customerPaymentSubmit').disabled = true;
    });

    // Named error bag prevents the shared layout from rendering payment errors at page top.
    @if($errors->getBag('customerPayment')->any() && old('payment_purchase_id'))
        const failedTrigger = [...document.querySelectorAll('[data-payment-open]')].find(b => b.dataset.purchaseId === @js((string) old('payment_purchase_id')));
        if (failedTrigger) {
            openModal(failedTrigger);
            form.elements['payment_date'].value = @js(old('payment_date', ''));
            form.elements['amount'].value = @js(old('amount', ''));
            form.elements['payment_method'].value = @js(old('payment_method', ''));
            form.elements['reference_no'].value = @js(old('reference_no', ''));
            @if($errors->getBag('customerPayment')->has('reference_no'))
                showReferenceError(@js($errors->getBag('customerPayment')->first('reference_no')));
            @endif
            @if($errors->getBag('customerPayment')->has('proof_image'))
                showProofError(@js($errors->getBag('customerPayment')->first('proof_image')));
            @endif
            @php
                $otherPaymentErrors = collect($errors->getBag('customerPayment')->messages())
                    ->except(['reference_no', 'proof_image'])
                    ->flatten()
                    ->implode(' ');
            @endphp
            @if($otherPaymentErrors)
                showServerErrors(@js($otherPaymentErrors));
            @endif
        }
    @endif
})();
</script>

<script>
(() => {
 const editOverlay=document.getElementById('customerEditPaymentOverlay');
 const auditOverlay=document.getElementById('customerAuditOverlay');
 const editForm=document.getElementById('customerEditPaymentForm');
 const editFile=document.getElementById('customerEditProofInput');
 const editWarning=document.getElementById('customerEditReceiptWarning');
 let editProofUrl='';
 let maxEditCents=0;
 const editAmountInput=document.getElementById('customerEditAmount');
 const checkEditBalance=(show)=>{const cents=Math.round(Number(editAmountInput.value)*100);if(!Number.isFinite(cents)||cents<1)return true;if(cents>maxEditCents){if(show)showEditWarning('Corrected payment exceeds the allowed total. Maximum corrected amount: ₱'+(maxEditCents/100).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})+'.');return false;}return true;};
 const openOverlay=(el)=>{if(!el)return;document.body.appendChild(el);el.classList.add('is-open');el.setAttribute('aria-hidden','false')};
 const closeOverlay=(el)=>{if(!el)return;el.classList.remove('is-open');el.setAttribute('aria-hidden','true')};
 const showEditWarning=(text)=>{editWarning.textContent=text;editWarning.classList.remove('hidden')};
 function openEdit(button){
   if(!editOverlay)return;
   editForm.reset();editWarning.classList.add('hidden');editWarning.textContent='';
   editForm.action=button.dataset.url;
   document.getElementById('customerEditTracking').textContent=button.dataset.tracking;
   document.getElementById('customerEditDate').value=button.dataset.date || '';
   document.getElementById('customerEditAmount').value=button.dataset.amount || '';
   maxEditCents=Math.round(Number(button.dataset.editMax||0)*100);
   document.getElementById('customerEditMaxHelp').textContent='Maximum allowed: ₱'+(maxEditCents/100).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
   document.getElementById('customerEditMethod').value=button.dataset.method || '';
   document.getElementById('customerEditReference').value=button.dataset.reference || '';
   document.getElementById('customerEditRemarks').value=button.dataset.remarks || '';
   document.getElementById('customerEditReason').value='';
   editProofUrl=button.dataset.proof || '';
   document.getElementById('customerEditOriginalProof').classList.toggle('hidden',!editProofUrl);
   openOverlay(editOverlay);
 }
 async function openHistory(button){
   document.getElementById('customerAuditTracking').textContent=button.dataset.tracking;
   const container=document.getElementById('customerAuditContent');container.replaceChildren();container.textContent='Loading history…';openOverlay(auditOverlay);
   try {
     const response=await fetch(button.dataset.paymentAudit,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
     if(!response.ok)throw new Error('Unable to load edit history (HTTP '+response.status+').');
     const records=await response.json();container.replaceChildren();
     if(!records.length){container.textContent='No edits recorded for this payment.';return;}
     for(const record of records){
       const section=document.createElement('section');section.className='mb-3 rounded-lg border border-slate-700 p-3';
       const head=document.createElement('div');head.className='mb-2 text-xs text-slate-300';head.textContent=`${record.date} · ${record.user} · ${record.action}`;section.append(head);
       const reason=document.createElement('p');reason.className='mb-2 text-xs text-amber-300';reason.textContent=record.description;section.append(reason);
       const table=document.createElement('table');table.className='customer-audit-table';
       const trh=document.createElement('tr');for(const title of ['Field','Before','After']){const th=document.createElement('th');th.textContent=title;trh.append(th)}table.append(trh);
       for(const c of record.changes){const tr=document.createElement('tr');for(const v of [c.field,c.old,c.new]){const td=document.createElement('td');td.textContent=v == null ? '—' : String(v);tr.append(td)}table.append(tr)}
       section.append(table);container.append(section);
     }
   }catch(err){container.textContent=err.message||'Unable to load history.';}
 }
 document.addEventListener('click',e=>{
   const edit=e.target.closest('[data-payment-edit]');if(edit){openEdit(edit);return;}
   const audit=e.target.closest('[data-payment-audit]');if(audit){openHistory(audit);return;}
 });
 document.getElementById('customerEditClose')?.addEventListener('click',()=>closeOverlay(editOverlay));
 document.getElementById('customerEditCancel')?.addEventListener('click',()=>closeOverlay(editOverlay));
 document.getElementById('customerAuditClose').addEventListener('click',()=>closeOverlay(auditOverlay));
 for(const el of [editOverlay,auditOverlay])el?.addEventListener('click',e=>{if(e.target===el)closeOverlay(el)});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(auditOverlay.classList.contains('is-open'))closeOverlay(auditOverlay);else if(editOverlay?.classList.contains('is-open'))closeOverlay(editOverlay)}});
 document.getElementById('customerEditOriginalButton')?.addEventListener('click',()=>{if(editProofUrl && typeof window.openPaymentProof==='function')window.openPaymentProof(editProofUrl)});
 editFile?.addEventListener('change',()=>{editWarning.classList.add('hidden');const f=editFile.files?.[0];if(!f)return;const ext=f.name.split('.').pop()?.toLowerCase();if(!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type)||!['jpg','jpeg','png','webp','gif'].includes(ext)){showEditWarning('Invalid file format! Only JPG, PNG, WEBP, and GIF are allowed.');editFile.value='';return;}if(f.size>5*1024*1024){showEditWarning('Receipt is too large. Maximum 5 MB.');editFile.value='';}});
 editAmountInput?.addEventListener('input',()=>{editWarning.classList.add('hidden');checkEditBalance(true)});
 editForm?.addEventListener('submit',e=>{if(!checkEditBalance(true)){e.preventDefault();editAmountInput.focus();return;}if(editWarning&&!editWarning.classList.contains('hidden')){e.preventDefault();return;}const f=editFile.files?.[0];if(f&&(!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type)||f.size>5*1024*1024)){e.preventDefault();showEditWarning('Invalid receipt. JPG, PNG, WEBP, GIF only, maximum 5 MB.');return;}document.getElementById('customerEditSave').disabled=true;});
})();
</script>

@endsection