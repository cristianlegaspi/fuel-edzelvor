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
</style>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-500" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400" role="alert">
        <div class="font-semibold">Payment could not be saved:</div>
        <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
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
                                        <button type="button" class="text-amber-600 dark:text-amber-400 font-medium hover:underline"
                                                data-proof-url="{{ route('fuel-customer-payments.proof', $payment) }}">View Proof</button>
                                    @else
                                        <span class="text-slate-400">No image</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">No payments recorded yet.</td></tr>
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
                            <tr><th class="px-3 py-3">Tracking No.</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Method</th><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Proof</th></tr>
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
                    <input type="hidden" name="payment_purchase_id" id="customerPaymentPurchaseId">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Payment Date
                            <input name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Amount
                            <input name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Payment Method
                            <select name="payment_method" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                                <option value="">Select Method</option>
                                @foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Reference No.
                            <input name="reference_no" type="text" value="{{ old('reference_no') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        </label>
                    </div>
                    <div>
                        <label for="customerProofInput" class="mb-1 block text-xs text-slate-500 dark:text-slate-400">Proof of Payment (image only)</label>
                        <input id="customerProofInput" name="proof_image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 p-2 text-sm">
                        <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP, GIF · Maximum 5 MB</p>
                        <div id="customerProofSelected" class="mt-3 hidden items-center gap-3">
                            <img id="customerProofThumb" class="h-16 w-16 rounded border object-cover" alt="Selected proof thumbnail">
                            <button type="button" id="customerProofPreview" class="text-sm font-semibold">Preview Image</button>
                            <button type="button" id="customerProofRemove" class="text-sm font-semibold">Remove</button>
                        </div>
                        <p id="customerProofError" class="mt-1 hidden text-xs text-red-500" role="alert"></p>
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
        fileError.classList.add('hidden');
        fileError.textContent = '';
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
        form.action = button.dataset.action;
        document.getElementById('customerPaymentPurchaseId').value = id;
        document.getElementById('customerPaymentModalSubtitle').textContent = button.dataset.customer + ' · ' + button.dataset.order;
        document.getElementById('customerPaymentPayables').textContent = '₱' + button.dataset.payables;
        document.getElementById('customerPaymentPaid').textContent = '₱' + button.dataset.paid;
        document.getElementById('customerPaymentBalance').textContent = '₱' + button.dataset.balance;
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
        fileError.classList.add('hidden');
        fileError.textContent = '';
        selected.classList.add('hidden');
        selected.classList.remove('flex');

        const file = fileInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)
            || file.size > 5 * 1024 * 1024) {
            fileError.textContent = 'Please choose a JPG, PNG, WEBP, or GIF image no larger than 5 MB.';
            fileError.classList.remove('hidden');
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
            fileError.textContent = 'Cannot read the selected image. Please choose another file.';
            fileError.classList.remove('hidden');
            fileInput.value = '';
        };
        reader.readAsDataURL(file);
    });
    thumb.addEventListener('error', () => {
        if (!fileInput.files?.length) return;
        fileError.textContent = 'Image preview is blocked or this file cannot be displayed. Check the browser Content Security Policy (img-src data:).';
        fileError.classList.remove('hidden');
    });
    document.getElementById('customerProofPreview').addEventListener('click', () => {
        if (previewUrl && typeof window.openPaymentProof === 'function') window.openPaymentProof(previewUrl);
    });
    document.getElementById('customerProofRemove').addEventListener('click', clearFile);
    form.addEventListener('submit', e => {
        const id = document.getElementById('customerPaymentPurchaseId').value;
        const trigger = [...document.querySelectorAll('[data-payment-open]')].find(b => b.dataset.purchaseId === id);
        if (!trigger || ['paid', 'overpaid'].includes(trigger.dataset.status)) {
            e.preventDefault();
            return;
        }
        document.getElementById('customerPaymentSubmit').disabled = true;
    });

    // On validation errors, reopen the corresponding modal with prior non-file values.
    @if($errors->any() && old('payment_purchase_id'))
        const failedTrigger = [...document.querySelectorAll('[data-payment-open]')].find(b => b.dataset.purchaseId === @js((string) old('payment_purchase_id')));
        if (failedTrigger) {
            openModal(failedTrigger);
            form.elements['payment_date'].value = @js(old('payment_date', ''));
            form.elements['amount'].value = @js(old('amount', ''));
            form.elements['payment_method'].value = @js(old('payment_method', ''));
            form.elements['reference_no'].value = @js(old('reference_no', ''));
        }
    @endif
})();
</script>
@endsection
