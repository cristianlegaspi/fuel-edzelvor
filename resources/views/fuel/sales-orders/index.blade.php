@extends('layouts.fuel')

@section('title', 'Supplier Orders')
@section('page-title', 'Supplier Orders')
@section('page-subtitle', 'Fuel purchased from suppliers')

@section('header-actions')
<a href="{{ route('fuel-sales-orders.create') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">+ New Order</a>

@endsection

@section('content')
@php
    // Supplier payment histories are loaded only for orders on the current page.
    $orders->getCollection()->loadMissing('payments');
@endphp
<style>
.supplier-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;color:#fff!important;font-size:12px;font-weight:700;white-space:nowrap;transition:background .2s,transform .2s,box-shadow .2s}
.supplier-action:hover{transform:translateY(-2px);box-shadow:0 5px 14px #0003}.supplier-action:focus-visible{outline:2px solid #fff;outline-offset:2px}
.supplier-edit{background:#d97706}.supplier-edit:hover{background:#f59e0b}.supplier-pay{background:#059669}.supplier-pay:hover{background:#10b981}.supplier-delete{background:#dc2626}.supplier-delete:hover{background:#ef4444}.supplier-view{background:#2563eb}.supplier-view:hover{background:#3b82f6}.supplier-lock{background:#334155;color:#cbd5e1!important;cursor:not-allowed}.supplier-lock:hover{transform:none;box-shadow:none}
/* Supplier table: compact on desktops, stacked readable cards on phones. */
.supplier-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.supplier-orders-table{width:100%;border-collapse:collapse;table-layout:auto}
.supplier-orders-table th,.supplier-orders-table td{padding:11px 10px;vertical-align:middle}
.supplier-orders-table th{font-size:11px}
.supplier-orders-table td{font-size:12px}
.supplier-orders-table .supplier-cell-order{min-width:170px}
.supplier-orders-table .supplier-cell-supplier{min-width:130px;max-width:230px;white-space:normal;overflow-wrap:anywhere}
.supplier-orders-table .supplier-cell-numeric{white-space:nowrap;font-variant-numeric:tabular-nums}
.supplier-orders-table .supplier-cell-actions{min-width:225px;white-space:normal}
.supplier-action-group{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:5px}
.supplier-action{padding:7px 9px;font-size:11px;line-height:1.25}
@media(max-width:767px){
 .supplier-orders-table,.supplier-orders-table tbody,.supplier-orders-table tr,.supplier-orders-table td{display:block;width:100%}
 .supplier-orders-table thead{display:none}
 .supplier-orders-table tbody{padding:10px;background:transparent}
 .supplier-orders-table tr{margin-bottom:12px;padding:10px 12px;border:1px solid #334155;border-radius:12px;background:rgba(30,41,59,.10)}
 .supplier-orders-table td{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:7px 0!important;border-bottom:1px solid rgba(148,163,184,.17);text-align:right!important;white-space:normal!important;min-width:0!important;max-width:none!important;overflow-wrap:anywhere}
 .supplier-orders-table td:last-child{border-bottom:0;padding-bottom:0!important}
 .supplier-orders-table td::before{content:attr(data-label);flex:0 0 36%;font-size:11px;font-weight:700;text-align:left;color:#94a3b8}
 .supplier-orders-table .supplier-cell-actions{display:block;text-align:left!important}
 .supplier-orders-table .supplier-cell-actions::before{display:block;margin-bottom:9px;content:'Actions'}
 .supplier-action-group{justify-content:flex-start}
 .supplier-orders-table .supplier-empty{display:block;text-align:center!important}
 .supplier-orders-table .supplier-empty::before{display:none}
}
#supplierPaymentModal{position:fixed!important;inset:0!important;z-index:9000!important;padding:12px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;visibility:hidden;opacity:0;pointer-events:none;background:rgba(2,6,23,.78);backdrop-filter:blur(3px);transition:opacity .22s ease,visibility .22s ease}
#supplierPaymentModal.supplier-open{visibility:visible;opacity:1;pointer-events:auto}
#supplierPaymentPanel{width:min(860px,100%);max-height:min(85dvh,780px);min-height:0;display:flex;flex-direction:column;transform:translateY(12px) scale(.97);opacity:0;transition:transform .22s ease,opacity .22s ease}
#supplierPaymentModal.supplier-open #supplierPaymentPanel{transform:none;opacity:1}
@media(max-width:640px){#supplierPaymentModal{padding:8px}#supplierPaymentPanel{max-height:94dvh}}
@media(prefers-reduced-motion:reduce){#supplierPaymentModal,#supplierPaymentPanel{transition:none}}

/* Explicit styles: independent of Tailwind build, including dark mode. */
.supplier-summary-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;width:100%}
.supplier-summary-card{min-width:0;border-radius:12px;border:1px solid;padding:13px 12px;box-shadow:0 4px 12px rgba(0,0,0,.06)}
.supplier-summary-label{font-size:12px;font-weight:600;opacity:.87}
.supplier-summary-value{margin-top:5px;font-weight:800;font-size:18px;line-height:1.35;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
.supplier-summary-net{background:#eff6ff!important;border-color:#bfdbfe!important;color:#1e40af!important}
.supplier-summary-paid{background:#ecfdf5!important;border-color:#a7f3d0!important;color:#047857!important}
.supplier-summary-balance{background:#fffbeb!important;border-color:#fde68a!important;color:#b45309!important}
.dark .supplier-summary-net{background:#172b4e!important;border-color:#315892!important;color:#bfdbfe!important}
.dark .supplier-summary-paid{background:#083b32!important;border-color:#047857!important;color:#a7f3d0!important}
.dark .supplier-summary-balance{background:#443115!important;border-color:#92601d!important;color:#fde68a!important}
#supplierModalSubmit{background:#059669!important;border:1px solid #047857!important;color:#fff!important;border-radius:8px;box-shadow:0 3px 12px rgba(5,150,105,.25);transition:background .2s,transform .2s}
#supplierModalSubmit:hover:not(:disabled){background:#047857!important;transform:translateY(-1px)}
#supplierProofSelected{padding:10px;border:1px solid #34d399;border-radius:10px;background:rgba(16,185,129,.07);flex-wrap:wrap}
#supplierProofThumb{width:64px;height:64px;display:block;object-fit:cover;border-radius:7px;border:1px solid #94a3b8;background:#f8fafc}
#supplierProofPreview{border-radius:7px;background:#d97706;color:#fff;padding:7px 10px;font-weight:700}
#supplierProofRemove{border-radius:7px;background:#dc2626;color:#fff;padding:7px 10px;font-weight:700}
@media(max-width:640px){.supplier-summary-row{gap:6px}.supplier-summary-card{padding:8px 6px}.supplier-summary-label{font-size:10px}.supplier-summary-value{font-size:clamp(10px,2.7vw,15px)}}
/* Highly visible invalid proof warning in light and dark themes. */
#supplierProofError.supplier-proof-alert {
    display: flex !important;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border: 1px solid #ef4444 !important;
    border-left: 4px solid #dc2626 !important;
    border-radius: 10px;
    background: #fef2f2 !important;
    color: #991b1b !important;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
}
.dark #supplierProofError.supplier-proof-alert {
    background: #450a0a !important;
    border-color: #f87171 !important;
    color: #fecaca !important;
}
#supplierProofError[hidden] { display: none !important; }
#supplierProofInput.supplier-file-invalid {
    border: 2px solid #ef4444 !important;
    outline: 1px solid rgba(239,68,68,.3);
}

.supplier-field-warning{display:none;margin-top:7px;padding:10px 12px;border:1px solid #ef4444;border-left:4px solid #dc2626;border-radius:8px;background:#450a0a;color:#fecaca;font-size:12px;font-weight:700;line-height:1.5}
.supplier-field-warning:not([hidden]){display:block}
.supplier-history-actions{display:flex;gap:5px;flex-wrap:wrap;align-items:center}
.supplier-history-btn{padding:6px 9px;border:0;border-radius:7px;color:white!important;font-size:11px;font-weight:700;cursor:pointer}
.supplier-history-btn.edit{background:#d97706}.supplier-history-btn.audit{background:#475569}.supplier-history-btn.proof{background:#2563eb}
.supplier-correction-overlay{position:fixed;inset:0;z-index:9300;display:flex;align-items:center;justify-content:center;padding:12px;background:rgba(2,6,23,.82);visibility:hidden;opacity:0;pointer-events:none;transition:opacity .2s,visibility .2s}
.supplier-correction-overlay.is-open{visibility:visible;opacity:1;pointer-events:auto}
.supplier-correction-panel{display:flex;flex-direction:column;width:min(670px,100%);max-height:90dvh;overflow:hidden;border-radius:14px;border:1px solid #475569;background:#0f172a;color:#e2e8f0;box-shadow:0 20px 60px #0009}
.supplier-correction-input{display:block;width:100%;box-sizing:border-box;margin-top:5px;border:1px solid #475569;border-radius:8px;background:#1e293b;color:#fff;padding:10px;font-size:13px}
.supplier-audit-table{border-collapse:collapse;width:100%;font-size:12px}.supplier-audit-table th,.supplier-audit-table td{padding:9px;border-bottom:1px solid #334155;text-align:left;vertical-align:top;overflow-wrap:anywhere}
#supplierCorrectionSave{background:#059669!important;color:#fff!important;padding:10px 18px;border-radius:8px;font-weight:700}
#supplierCorrectionSave:hover{background:#047857!important}
</style>

@if(session('success'))<div class="mb-4 rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400" role="status">{{ session('success') }}</div>@endif

<form method="GET" class="mb-4 flex flex-wrap gap-3">
<input type="text" name="search" value="{{ request('search') }}" placeholder="Search order no. or supplier..." class="w-full sm:w-72 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
<select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm"><option value="">All statuses</option>@foreach(['unpaid','partial','paid','overpaid'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<button type="submit" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>
<div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"><div class="supplier-table-wrap"><table class="supplier-orders-table text-sm"><thead class="border-b border-slate-100 bg-slate-50 text-left text-slate-400 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-500"><tr>
<th class="px-6 py-3 font-medium whitespace-nowrap">Order No.</th><th class="px-6 py-3 font-medium whitespace-nowrap">Date Ordered</th><th class="px-6 py-3 font-medium whitespace-nowrap">Supplier</th><th class="px-6 py-3 font-medium whitespace-nowrap">Total Liters</th><th class="px-6 py-3 font-medium whitespace-nowrap">Net Amount</th><th class="px-6 py-3 font-medium whitespace-nowrap">Balance</th><th class="px-6 py-3 font-medium whitespace-nowrap">Status</th><th class="px-4 py-3 font-medium whitespace-nowrap text-right">Actions</th>
</tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
@forelse($orders as $order)
@php
$status = strtolower(trim((string)$order->status));
$locked = in_array($status,['paid','overpaid'],true);
$payments = $order->payments->sortByDesc('id');
$totalPaid = (float)$payments->sum('amount');
$balance = max(0,(float)$order->net_amount - $totalPaid);
@endphp
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
<td data-label="Order No." class="supplier-cell-order font-medium">@if($locked)<span class="text-slate-700 dark:text-slate-200">{{ $order->sales_order_no }}</span>@else<a class="text-amber-600 hover:underline dark:text-amber-400" href="{{ route('fuel-sales-orders.edit',$order) }}">{{ $order->sales_order_no }}</a>@endif</td>
<td data-label="Date Ordered" class="text-slate-600 dark:text-slate-300">{{ optional($order->date_ordered)->format('M d, Y') }}</td>
<td data-label="Supplier" class="supplier-cell-supplier text-slate-600 dark:text-slate-300">{{ $order->supplier }}</td>
<td data-label="Total Liters" class="supplier-cell-numeric text-slate-600 dark:text-slate-300">{{ number_format($order->total_liters,2) }} L</td>
<td data-label="Net Amount" class="supplier-cell-numeric text-slate-600 dark:text-slate-300">₱{{ number_format($order->net_amount,2) }}</td>
<td data-label="Balance" class="supplier-cell-numeric text-slate-600 dark:text-slate-300">₱{{ number_format($order->balance_amount,2) }}</td>
<td data-label="Status"><x-badge :color="match($status) {'paid'=>'green','partial'=>'yellow','overpaid'=>'blue',default=>'red'}">{{ ucfirst($status) }}</x-badge></td>
<td data-label="Actions" class="supplier-cell-actions"><div class="supplier-action-group">
@if(!$locked)<a class="supplier-action supplier-edit" href="{{ route('fuel-sales-orders.edit',$order) }}">✎ Edit</a>@endif
<button type="button" class="supplier-action {{ $locked?'supplier-view':'supplier-pay' }}" data-supplier-open data-id="{{ $order->getKey() }}" data-supplier="{{ $order->supplier }}" data-order="{{ $order->sales_order_no }}" data-status="{{ $status }}" data-net="{{ number_format((float)$order->net_amount,2) }}" data-paid="{{ number_format($totalPaid,2) }}" data-balance="{{ number_format($balance,2) }}" data-action="{{ route('fuel-sales-orders.payments.store',$order) }}">{{ $locked?'◉ View Payments':'▣ Add Payment' }}</button>
@if($locked)<span class="supplier-action supplier-lock">♙ Locked</span>@else
<form action="{{ route('fuel-sales-orders.destroy',$order) }}" method="POST" class="inline-flex" data-confirm="This will permanently delete the supplier order and its items. This cannot be undone." data-confirm-type="danger" data-confirm-title="Delete this order?">@csrf @method('DELETE')<button type="submit" class="supplier-action supplier-delete">▤ Delete</button></form>
@endif
</div></td></tr>
@empty<tr><td colspan="8" class="supplier-empty px-6 py-10 text-center text-slate-400">No supplier orders found.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-6"><x-pagination :paginator="$orders" /></div>

{{-- Templates are kept outside the table to ensure valid HTML. --}}
@foreach($orders as $order)
<template id="supplier-history-{{ $order->getKey() }}">
@forelse($order->payments->sortByDesc('id') as $payment)
<tr class="border-b border-slate-200 dark:border-slate-800">
<td class="px-3 py-3 whitespace-nowrap">{{ optional($payment->payment_date)->format('M d, Y') ?? '—' }}</td>
<td class="px-3 py-3 font-semibold whitespace-nowrap">₱{{ number_format($payment->amount,2) }}</td>
<td class="px-3 py-3">{{ $payment->payment_method ?: '—' }}</td>
<td class="px-3 py-3">{{ $payment->reference_no ?: '—' }}</td>
<td class="px-3 py-3">@if($payment->proof_image)<button type="button" data-supplier-proof-url="{{ route('fuel-payments.proof',$payment) }}" class="supplier-history-btn proof">View Proof</button>@else — @endif</td>
<td class="px-3 py-3"><div class="supplier-history-actions">
@if(auth()->user()?->isAdminOrAbove())
<button type="button" class="supplier-history-btn edit" data-supplier-edit
 data-url="{{ route('fuel-payments.update', $payment) }}"
 data-date="{{ optional($payment->payment_date)->format('Y-m-d') }}"
 data-amount="{{ $payment->amount }}" data-method="{{ $payment->payment_method }}"
 data-reference="{{ $payment->reference_no }}" data-remarks="{{ $payment->remarks }}"
 data-proof="{{ $payment->proof_image ? route('fuel-payments.proof', $payment) : '' }}"
 data-order-id="{{ $order->getKey() }}" data-max-amount="{{ number_format(max(0,(float)$order->net_amount - ((float)$order->payments->sum('amount') - (float)$payment->amount)),2,'.','') }}">✎ Edit</button>
<button type="button" class="supplier-history-btn audit" data-supplier-audit="{{ route('fuel-payments.history', $payment) }}">History</button>
@endif
</div></td>
</tr>
@empty<tr><td colspan="6" class="px-3 py-7 text-center text-slate-400">No payments recorded yet.</td></tr>@endforelse
</template>
@endforeach

{{-- One shared Supplier Payment modal per page --}}
<div id="supplierPaymentModal" role="dialog" aria-modal="true" aria-labelledby="supplierModalTitle" aria-hidden="true">
<div id="supplierPaymentPanel" class="overflow-hidden rounded-2xl border border-slate-700 bg-white shadow-2xl dark:bg-slate-900" tabindex="-1">
<div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"><div><h2 id="supplierModalTitle" class="text-lg font-bold text-slate-900 dark:text-white">Supplier Payments</h2><p id="supplierModalSubtitle" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p></div><button id="supplierModalClose" type="button" aria-label="Close modal" class="rounded-lg px-2 py-1 text-xl text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800">&times;</button></div>
<div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-4 sm:p-5">
<div class="supplier-summary-row">
<div class="supplier-summary-card supplier-summary-net"><div class="supplier-summary-label">Net Amount</div><div id="supplierModalNet" class="supplier-summary-value"></div></div>
<div class="supplier-summary-card supplier-summary-paid"><div class="supplier-summary-label">Total Paid</div><div id="supplierModalPaid" class="supplier-summary-value"></div></div>
<div class="supplier-summary-card supplier-summary-balance"><div class="supplier-summary-label">Remaining Balance</div><div id="supplierModalBalance" class="supplier-summary-value"></div></div>
</div>
<section><h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Payment History</h3><div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700"><table class="min-w-[760px] w-full text-xs text-slate-700 dark:text-slate-300"><thead class="bg-slate-100 text-left text-slate-500 dark:bg-slate-800 dark:text-slate-400"><tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Method</th><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Proof</th><th class="px-3 py-3">Actions</th></tr></thead><tbody id="supplierHistoryBody"></tbody></table></div></section>
<div id="supplierModalLocked" class="hidden rounded-lg border border-slate-600 bg-slate-100 px-4 py-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-300">This supplier order is paid or overpaid. Payment history is view-only and additional payments are disabled.</div>
<section id="supplierPaymentFormSection"><h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Add Supplier Payment</h3>
<form id="supplierPaymentForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4">@csrf
<input type="hidden" name="payment_order_id" id="supplierOrderId">
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
<label class="block text-xs text-slate-500 dark:text-slate-400">Payment Date<input name="payment_date" type="date" value="{{ old('payment_date',now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-600"></label>
<label class="block text-xs text-slate-500 dark:text-slate-400">Amount<input name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-600"></label>
<label class="block text-xs text-slate-500 dark:text-slate-400">Payment Method<select name="payment_method" required class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-600"><option value="">Select Method</option>@foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $method)<option value="{{ $method }}" @selected(old('payment_method')===$method)>{{ $method }}</option>@endforeach</select></label>
<label class="block text-xs text-slate-500 dark:text-slate-400">Reference No.<input name="reference_no" id="supplierReferenceInput" required maxlength="255" type="text" value="{{ old('reference_no') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-600"></label>
</div>
<div id="supplierReferenceWarning" class="supplier-field-warning" role="alert" hidden></div>
<div id="supplierMethodWarning" class="supplier-field-warning" role="alert" hidden></div>
<div id="supplierModalOtherErrors" class="supplier-field-warning" role="alert" hidden></div>
<div><label for="supplierProofInput" class="mb-1 block text-xs text-slate-500 dark:text-slate-400">Proof of Payment (image only)</label><input id="supplierProofInput" name="proof_image" type="file" required aria-required="true" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="w-full rounded-lg border border-slate-300 p-2 text-sm dark:border-slate-600"><p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP, GIF · Maximum 5 MB</p><div id="supplierProofSelected" class="mt-3 hidden items-center gap-3"><img id="supplierProofThumb" alt="Selected proof" class="h-14 w-14 rounded border object-cover"><button id="supplierProofPreview" type="button" class="text-sm font-semibold text-amber-500 hover:underline">Preview Image</button><button id="supplierProofRemove" type="button" class="text-sm font-semibold text-red-500 hover:underline">Remove</button></div><div id="supplierProofError" class="mt-3" role="alert" aria-live="assertive" hidden>
  <span aria-hidden="true" style="font-size:19px;line-height:1.2">⚠</span>
  <span id="supplierProofErrorText"></span>
</div></div>
<div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700"><button type="button" id="supplierModalCancel" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button><button id="supplierModalSubmit" type="submit" style="background:#059669;color:#fff" class="rounded-lg px-5 py-2 text-sm font-bold shadow-sm transition hover:brightness-110 disabled:opacity-60">Save Payment</button></div>
</form></section>
</div></div></div>


{{-- Authorized corrections and audit trail --}}
@if(auth()->user()?->isAdminOrAbove())
<div id="supplierCorrectionOverlay" class="supplier-correction-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="supplierCorrectionTitle">
 <div class="supplier-correction-panel">
  <header class="flex items-center justify-between border-b border-slate-700 p-4"><h3 id="supplierCorrectionTitle" class="font-bold">Edit Supplier Payment</h3><button type="button" id="supplierCorrectionClose" aria-label="Close" class="text-xl">&times;</button></header>
  <div class="overflow-y-auto p-4"><p class="mb-3 rounded-lg border border-amber-700 bg-amber-950/40 p-3 text-xs text-amber-200">Financial correction requires a reason. Previous values are retained in Audit Logs.</p>
  <form id="supplierCorrectionForm" method="POST" enctype="multipart/form-data" class="space-y-3">@csrf @method('PATCH')
  <input type="hidden" name="payment_order_id" id="supplierCorrectionOrderId">
  <input type="hidden" name="payment_edit_url" id="supplierCorrectionEditUrl">
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
   <label class="text-xs">Payment Date<input id="supplierCorrectionDate" type="date" name="payment_date" class="supplier-correction-input"></label>
   <label class="text-xs">Amount<input id="supplierCorrectionAmount" type="number" name="amount" min="0.01" step="0.01" required class="supplier-correction-input"></label>
   <label class="text-xs">Payment Method<select id="supplierCorrectionMethod" name="payment_method" required class="supplier-correction-input"><option value="">Select Method</option>@foreach(['Cash','Check','Bank Transfer','Online Transfer','GCash','Others'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></label>
   <label class="text-xs">Reference No.<input id="supplierCorrectionReference" name="reference_no" required maxlength="255" class="supplier-correction-input"></label>
  </div>
  <label class="block text-xs">Remarks<textarea id="supplierCorrectionRemarks" name="remarks" rows="2" class="supplier-correction-input"></textarea></label>
  <label class="block text-xs">Reason for Correction <span class="text-red-400">*</span><textarea id="supplierCorrectionReason" name="edit_reason" required minlength="10" maxlength="1000" rows="2" class="supplier-correction-input" placeholder="Explain why this payment must be corrected"></textarea></label>
  <label class="block text-xs">Replace Receipt (optional)<input id="supplierCorrectionReceipt" name="proof_image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="supplier-correction-input"><span class="text-slate-400">JPG, PNG, WEBP, GIF · Maximum 5 MB</span></label>
  <button id="supplierCorrectionExistingProof" type="button" class="hidden text-xs font-semibold text-blue-400 hover:underline">View existing receipt</button>
  <p id="supplierCorrectionMaximum" class="text-xs text-amber-300"></p>
  <div id="supplierCorrectionWarning" class="supplier-field-warning" role="alert" hidden></div>
  <footer class="flex justify-end gap-2 border-t border-slate-700 pt-3"><button id="supplierCorrectionCancel" type="button" class="rounded-lg border border-slate-500 px-4 py-2 text-sm">Cancel</button><button id="supplierCorrectionSave" type="submit">Save Correction</button></footer>
  </form></div>
 </div>
</div>
<div id="supplierAuditOverlay" class="supplier-correction-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="supplierAuditTitle">
 <div class="supplier-correction-panel"><header class="flex items-center justify-between border-b border-slate-700 p-4"><h3 id="supplierAuditTitle" class="font-bold">Supplier Payment Edit History</h3><button type="button" id="supplierAuditClose" class="text-xl" aria-label="Close">&times;</button></header><div id="supplierAuditContent" class="overflow-y-auto p-4 text-sm">Loading…</div></div>
</div>
@endif

{{-- Keep one reusable image proof modal only. --}}
@include('fuel.partials.payment-proof-modal')
<script>
(function () {
 const modal=document.getElementById('supplierPaymentModal'),panel=document.getElementById('supplierPaymentPanel'),form=document.getElementById('supplierPaymentForm'),body=document.getElementById('supplierHistoryBody'),file=document.getElementById('supplierProofInput'),selected=document.getElementById('supplierProofSelected'),thumb=document.getElementById('supplierProofThumb'),err=document.getElementById('supplierProofError');
 let lastTrigger=null,oldOverflow='',previewUrl=null,closeTimer=null,fileReadToken=0;
 // Move outside transformed or clipped layout ancestors.
 document.body.appendChild(modal);const proofOverlay=document.getElementById('paymentProofModal');if(proofOverlay&&proofOverlay.parentElement!==document.body)document.body.appendChild(proofOverlay);
 function clearFile(){fileReadToken++;file.value='';previewUrl=null;thumb.removeAttribute('src');selected.classList.add('hidden');selected.classList.remove('flex');err.hidden=true;err.classList.remove('supplier-proof-alert');file.classList.remove('supplier-file-invalid');document.getElementById('supplierProofErrorText').textContent=''}
 function close(){if(!modal.classList.contains('supplier-open'))return;modal.classList.remove('supplier-open');modal.setAttribute('aria-hidden','true');document.body.style.overflow=oldOverflow;if(closeTimer)clearTimeout(closeTimer);closeTimer=setTimeout(()=>{clearFile();lastTrigger?.focus()},230)}
 function open(btn){if(closeTimer)clearTimeout(closeTimer);lastTrigger=btn;const id=btn.dataset.id,locked=['paid','overpaid'].includes(btn.dataset.status);const tpl=document.getElementById('supplier-history-'+id);body.replaceChildren();if(tpl)body.appendChild(tpl.content.cloneNode(true));form.reset();clearFile();['supplierReferenceWarning','supplierMethodWarning','supplierModalOtherErrors'].forEach(id=>{const e=document.getElementById(id);e.textContent='';e.hidden=true});form.action=btn.dataset.action;document.getElementById('supplierOrderId').value=id;document.getElementById('supplierModalSubtitle').textContent=btn.dataset.supplier+' · '+btn.dataset.order;document.getElementById('supplierModalNet').textContent='₱'+btn.dataset.net;document.getElementById('supplierModalPaid').textContent='₱'+btn.dataset.paid;document.getElementById('supplierModalBalance').textContent='₱'+btn.dataset.balance;document.getElementById('supplierModalLocked').classList.toggle('hidden',!locked);document.getElementById('supplierPaymentFormSection').classList.toggle('hidden',locked);oldOverflow=document.body.style.overflow;document.body.style.overflow='hidden';modal.classList.add('supplier-open');modal.setAttribute('aria-hidden','false');document.getElementById('supplierModalClose').focus()}
 document.querySelectorAll('[data-supplier-open]').forEach(btn=>btn.addEventListener('click',()=>open(btn)));
 document.getElementById('supplierModalClose').addEventListener('click',close);document.getElementById('supplierModalCancel').addEventListener('click',close);modal.addEventListener('click',e=>{if(e.target===modal)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape' && modal.classList.contains('supplier-open') && document.getElementById('paymentProofModal')?.classList.contains('hidden'))close()});
 body.addEventListener('click',e=>{const b=e.target.closest('[data-supplier-proof-url]');if(b && typeof window.openPaymentProof==='function')window.openPaymentProof(b.dataset.supplierProofUrl)});
   function hideFileError() {
    err.hidden = true;
    err.classList.remove('supplier-proof-alert');
    file.classList.remove('supplier-file-invalid');
    document.getElementById('supplierProofErrorText').textContent = '';
  }
  function showFileError(message) {
    document.getElementById('supplierProofErrorText').textContent = message;
    err.hidden = false;
    err.classList.add('supplier-proof-alert');
    file.classList.add('supplier-file-invalid');
  }
  function clearPreview() {
    fileReadToken++;
    previewUrl = null;
    thumb.removeAttribute('src');
    selected.classList.add('hidden');
    selected.classList.remove('flex');
    hideFileError();
  }
  file.addEventListener('change', () => {
    const f = file.files?.[0];
    clearPreview();
    if (!f) return;

    const allowedTypes = ['image/jpeg','image/png','image/webp','image/gif'];
    const allowedExtensions = /\.(jpe?g|png|webp|gif)$/i;
    if (!allowedTypes.includes(f.type) || !allowedExtensions.test(f.name)) {
      showFileError('Invalid file format! Only JPG, PNG, WEBP, and GIF images are accepted. PDF, Word, Excel, and other files are not allowed.');
      file.value = '';
      return;
    }
    if (f.size > 5 * 1024 * 1024) {
      showFileError('File is too large! Maximum allowed image size is 5 MB. Please choose a smaller image.');
      file.value = '';
      return;
    }

    const token = ++fileReadToken;
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
      showFileError('Unable to read the selected image. Please choose another image.');
      file.value = '';
    };
    reader.readAsDataURL(f);
  });
  thumb.addEventListener('error', () => {
    if (file.files?.length) {
      showFileError('Image preview is unavailable. Check the browser image Content Security Policy (img-src data:).');
    }
  });
document.getElementById('supplierProofRemove').addEventListener('click',clearFile);document.getElementById('supplierProofPreview').addEventListener('click',()=>{if(previewUrl&&typeof window.openPaymentProof==='function')window.openPaymentProof(previewUrl)});
 // Native required-file validation does not emit submit; show our own red warning.
 file.addEventListener('invalid', event => {
   if (!file.files?.length) {
     event.preventDefault();
     showFileError('Receipt is required! Please attach a JPG, PNG, WEBP, or GIF image (maximum 5 MB) before saving.');
   }
 });
 form.addEventListener('submit', event => {
   const btn = [...document.querySelectorAll('[data-supplier-open]')]
       .find(b => b.dataset.id === document.getElementById('supplierOrderId').value);
   if (!btn || ['paid', 'overpaid'].includes(btn.dataset.status)) {
     event.preventDefault();
     return;
   }
   if (!form.elements['payment_method'].value) { event.preventDefault();const e=document.getElementById('supplierMethodWarning');e.textContent='Please select a payment method.';e.hidden=false;return; }
   if (!form.elements['reference_no'].value.trim()) { event.preventDefault();const e=document.getElementById('supplierReferenceWarning');e.textContent='Reference No. is required.';e.hidden=false;return; }
   const receipt = file.files?.[0];
   if (!receipt) {
     event.preventDefault();
     showFileError('Receipt is required! Please attach a JPG, PNG, WEBP, or GIF image (maximum 5 MB) before saving.');
     file.focus();
     return;
   }
   const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
   if (!allowedTypes.includes(receipt.type) || !/\.(jpe?g|png|webp|gif)$/i.test(receipt.name)) {
     event.preventDefault();
     showFileError('Invalid file format! Only JPG, PNG, WEBP, and GIF images are accepted.');
     file.value = '';
     return;
   }
   if (receipt.size > 5 * 1024 * 1024) {
     event.preventDefault();
     showFileError('File is too large! Maximum allowed receipt size is 5 MB.');
     file.value = '';
     return;
   }
   hideFileError();
   document.getElementById('supplierModalSubmit').disabled = true;
 });
 @if($errors->supplierPayment->any() && old('payment_order_id'))
 const failed=[...document.querySelectorAll('[data-supplier-open]')].find(b=>b.dataset.id===@js((string)old('payment_order_id')));
 if(failed){open(failed);form.elements['payment_date'].value=@js(old('payment_date',''));form.elements['amount'].value=@js(old('amount',''));form.elements['payment_method'].value=@js(old('payment_method',''));form.elements['reference_no'].value=@js(old('reference_no',''));
  const show=(id,message)=>{const el=document.getElementById(id);el.textContent=message;el.hidden=false};
  @if($errors->supplierPayment->has('reference_no')) show('supplierReferenceWarning',@js($errors->supplierPayment->first('reference_no'))); @endif
  @if($errors->supplierPayment->has('payment_method')) show('supplierMethodWarning',@js($errors->supplierPayment->first('payment_method'))); @endif
  @if($errors->supplierPayment->has('proof_image')) showFileError(@js($errors->supplierPayment->first('proof_image'))); @endif
  @if($errors->supplierPayment->has('amount')) show('supplierModalOtherErrors',@js($errors->supplierPayment->first('amount'))); @endif
  }
 @endif
})();
</script>

<script>
(() => {
 const edit = document.getElementById('supplierCorrectionOverlay');
 const audit = document.getElementById('supplierAuditOverlay');
 if (!edit || !audit) return;
 const form = document.getElementById('supplierCorrectionForm');
 const receipt = document.getElementById('supplierCorrectionReceipt');
 const warning = document.getElementById('supplierCorrectionWarning');
 const current = document.getElementById('supplierCorrectionExistingProof');
 let existingUrl = '';
 [edit,audit].forEach(el => document.body.appendChild(el));
 function open(el){el.classList.add('is-open');el.setAttribute('aria-hidden','false')}
 function close(el){el.classList.remove('is-open');el.setAttribute('aria-hidden','true')}
 function warn(s){warning.textContent=s;warning.hidden=false}
 document.addEventListener('click', event => {
  const button=event.target.closest('[data-supplier-edit]');
  if(button){
   form.reset();warning.hidden=true;warning.textContent='';
   form.action=button.dataset.url;
   document.getElementById('supplierCorrectionEditUrl').value=button.dataset.url;
   document.getElementById('supplierCorrectionOrderId').value=button.dataset.orderId;
   document.getElementById('supplierCorrectionDate').value=button.dataset.date || '';
   document.getElementById('supplierCorrectionAmount').value=button.dataset.amount || '';
   const maxAmount=Number(button.dataset.maxAmount || 0);
   document.getElementById('supplierCorrectionAmount').dataset.maximum=maxAmount.toFixed(2);
   document.getElementById('supplierCorrectionMaximum').textContent='Maximum allowable corrected payment: ₱'+maxAmount.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
   document.getElementById('supplierCorrectionMethod').value=button.dataset.method || '';
   document.getElementById('supplierCorrectionReference').value=button.dataset.reference || '';
   document.getElementById('supplierCorrectionRemarks').value=button.dataset.remarks || '';
   document.getElementById('supplierCorrectionReason').value='';
   existingUrl=button.dataset.proof || '';current.classList.toggle('hidden',!existingUrl);
   open(edit);return;
  }
  const history=event.target.closest('[data-supplier-audit]');
  if(history){
   const area=document.getElementById('supplierAuditContent');area.textContent='Loading edit history…';open(audit);
   fetch(history.dataset.supplierAudit,{credentials:'same-origin',headers:{Accept:'application/json'}})
    .then(r=>{if(!r.ok)throw new Error('Unable to load history (HTTP '+r.status+').');return r.json()})
    .then(records=>{area.replaceChildren();if(!records.length){area.textContent='No edits recorded.';return}
      records.forEach(record=>{const section=document.createElement('section');section.className='mb-4 rounded-lg border border-slate-700 p-3';
      const h=document.createElement('p');h.className='mb-2 text-xs font-semibold';h.textContent=[record.date,record.user,record.description].join(' · ');section.append(h);
      const table=document.createElement('table');table.className='supplier-audit-table';const heading=document.createElement('tr');
      ['Field','Before','After'].forEach(label=>{const th=document.createElement('th');th.textContent=label;heading.append(th)});table.append(heading);
      (record.changes||[]).forEach(c=>{const tr=document.createElement('tr');[c.field,c.old,c.new].forEach(v=>{const td=document.createElement('td');td.textContent=v==null?'—':String(v);tr.append(td)});table.append(tr)});
      section.append(table);area.append(section)});
    }).catch(error=>{area.textContent=error.message});
  }
 });
 document.getElementById('supplierCorrectionClose').onclick=()=>close(edit);
 document.getElementById('supplierCorrectionCancel').onclick=()=>close(edit);
 document.getElementById('supplierAuditClose').onclick=()=>close(audit);
 [edit,audit].forEach(el=>el.addEventListener('click',e=>{if(e.target===el)close(el)}));
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(audit.classList.contains('is-open'))close(audit);else if(edit.classList.contains('is-open'))close(edit)}});
 current.onclick=()=>{if(existingUrl&&typeof window.openPaymentProof==='function')window.openPaymentProof(existingUrl)};
 receipt.addEventListener('change',()=>{warning.hidden=true;const f=receipt.files?.[0];if(!f)return;
   if(!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type)||! /\.(jpe?g|png|webp|gif)$/i.test(f.name)){warn('Only JPG, PNG, WEBP, GIF images are allowed.');receipt.value=''}
   else if(f.size>5*1024*1024){warn('Maximum receipt size is 5 MB.');receipt.value=''}
 });
 form.addEventListener('submit',e=>{
   const amount=Number(document.getElementById('supplierCorrectionAmount').value);
   const max=Number(document.getElementById('supplierCorrectionAmount').dataset.maximum);
   if(!Number.isFinite(amount)||amount<=0||amount>max+0.00001){e.preventDefault();warn('Payment correction exceeds the allowable balance. Maximum amount is ₱'+max.toFixed(2)+'.');return;}
   const f=receipt.files?.[0];if(f&&(f.size>5*1024*1024||!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type))){e.preventDefault();warn('Invalid receipt; JPG, PNG, WEBP, GIF, max 5 MB.');return}
   document.getElementById('supplierCorrectionSave').disabled=true;
 });
 @if($errors->supplierCorrection->any() && old('payment_order_id'))
   const priorOrder = @js((string) old('payment_order_id'));
   // A single purchase may have multiple payments; use a dedicated payment URL in the form when available.
   const selectedButton = [...document.querySelectorAll('[data-supplier-edit]')]
     .find(b => b.dataset.url === @js(old('payment_edit_url', '')));
   if(selectedButton) {
     selectedButton.click();
     document.getElementById('supplierCorrectionDate').value = @js(old('payment_date', ''));
     document.getElementById('supplierCorrectionAmount').value = @js(old('amount', ''));
     document.getElementById('supplierCorrectionMethod').value = @js(old('payment_method', ''));
     document.getElementById('supplierCorrectionReference').value = @js(old('reference_no', ''));
     document.getElementById('supplierCorrectionRemarks').value = @js(old('remarks', ''));
     document.getElementById('supplierCorrectionReason').value = @js(old('edit_reason', ''));
     warn(@js(implode(' ', $errors->supplierCorrection->all())));
   }
 @endif
})();
</script>

@endsection
