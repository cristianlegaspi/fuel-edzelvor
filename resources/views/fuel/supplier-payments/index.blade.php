@extends('layouts.fuel')

@section('title', 'Supplier Payments')
@section('page-title', 'Supplier Payments')
@section('page-subtitle', 'All payments made to suppliers, across every sales order')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reference no., SO no., or supplier..."
           class="w-full sm:w-80 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Payment Date</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Sales Order</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Supplier</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Amount</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Method</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Reference No.</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Payment Proof</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($payments as $payment)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ optional($payment->payment_date)->format('M d, Y') ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-amber-600 dark:text-amber-400">
                        @if($payment->salesOrder)
                            <a href="{{ route('fuel-sales-orders.edit', $payment->salesOrder) }}" class="hover:underline">{{ $payment->salesOrder->sales_order_no }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->salesOrder->supplier ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-800 dark:text-white">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->payment_method ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $payment->reference_no ?? '—' }}</td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        @if($payment->proof_image)
                            <button type="button"
                                    onclick="openPaymentProof(@js(route('fuel-payments.proof', $payment)))"
                                    class="text-amber-600 dark:text-amber-400 font-medium hover:underline">
                                View Proof
                            </button>
                        @else
                            <span class="text-slate-400 dark:text-slate-500">No Image</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right space-x-2">
                        @if($payment->salesOrder)
                            <a href="{{ route('fuel-payments.soa.print', $payment->salesOrder) }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline" target="_blank">SOA</a>
                        @endif
                        <form action="{{ route('fuel-payments.destroy', $payment) }}" method="POST" class="inline" data-confirm="This payment record will be permanently removed." data-confirm-type="danger" data-confirm-title="Remove this payment?">
                            @csrf @method('DELETE')
                            <button class="text-red-600 dark:text-red-400 text-sm font-medium hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No supplier payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6"><x-pagination :paginator="$payments" /></div>
{{-- Resizable receipt preview; mouse wheel zooms while pointer is over the image --}}
<div id="paymentProofModal"
     class="hidden fixed inset-0 z-[9999] items-center justify-center bg-black/65 p-3 sm:p-5"
     role="dialog" aria-modal="true" aria-label="Proof of Payment"
     onclick="if(event.target === this) closePaymentProof()">
    <div id="paymentProofWindow"
         class="relative flex flex-col rounded-xl bg-white dark:bg-slate-900 shadow-2xl"
         style="width:440px;height:570px;min-width:280px;min-height:230px;max-width:calc(100vw - 24px);max-height:calc(100dvh - 24px);overflow:hidden;">
        <div id="paymentProofDragHandle" class="flex shrink-0 cursor-move select-none touch-none items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 px-3 py-2" style="touch-action:none;">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Proof of Payment</h3>
            <div class="flex items-center gap-1">
                <button type="button" onclick="zoomPaymentProof(-0.2)" title="Zoom out"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm">−</button>
                <span id="paymentProofZoomLabel" class="w-12 text-center text-xs text-slate-500 dark:text-slate-300">100%</span>
                <button type="button" onclick="zoomPaymentProof(0.2)" title="Zoom in"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm">+</button>
                <button type="button" onclick="resetPaymentProofZoom()"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-xs">Fit</button>
                <button type="button" onclick="closePaymentProof()" aria-label="Close preview"
                        class="ml-1 rounded px-2 py-1 text-xl text-slate-500 hover:text-red-600">&times;</button>
            </div>
        </div>
        <div id="paymentProofImageContainer"
             class="min-h-0 flex-1 overflow-auto bg-slate-100 dark:bg-slate-950"
             style="overscroll-behavior:contain;">
            <div id="paymentProofCanvas" class="relative mx-auto" style="min-width:100%;min-height:100%;">
                <img id="paymentProofModalImage" alt="Proof of Payment"
                     draggable="false"
                     class="absolute block rounded-sm"
                     style="max-width:none;max-height:none;user-select:none;">
            </div>
        </div>
        <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 px-3 py-2">
            <span class="text-xs text-slate-400">Mouse wheel: zoom · Drag handle to resize</span>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" onclick="closePaymentProof()"
                        class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white hover:bg-slate-700">Close</button>
                <div id="paymentProofResizeHandle"
                     title="Drag to resize preview"
                     role="separator" aria-label="Resize proof preview"
                     class="flex h-7 w-7 cursor-nwse-resize touch-none select-none items-end justify-end rounded border border-slate-300 dark:border-slate-600 p-1 text-slate-500"
                     style="touch-action:none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M21 15l-6 6M21 9L9 21"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
let paymentProofZoom = 1;
let previousBodyOverflow = '';

function openPaymentProof(url) {
    const modal = document.getElementById('paymentProofModal');
    const image = document.getElementById('paymentProofModalImage');
    const panel = document.getElementById('paymentProofWindow');
    const viewport = document.getElementById('paymentProofImageContainer');

    paymentProofZoom = 1;

    panel.style.width = Math.min(440, window.innerWidth - 24) + 'px';
    panel.style.height = Math.min(570, window.innerHeight - 24) + 'px';
    panel.style.transform = 'translate(0px, 0px)';

    proofModalOffsetX = 0;
    proofModalOffsetY = 0;

    image.onload = function () {
        renderPaymentProof();
    };

    image.onerror = function () {
        console.error('Payment proof failed to load:', url);
        console.error('Image natural size:', image.naturalWidth, image.naturalHeight);

        alert('Unable to display payment proof image. Check browser Console and Network Preview.');
    };

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    viewport.scrollTop = 0;
    viewport.scrollLeft = 0;

    image.removeAttribute('src');
    image.src = url;
}

function closePaymentProof() {
    const modal = document.getElementById('paymentProofModal');
    if (modal.classList.contains('hidden')) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const image = document.getElementById('paymentProofModalImage');
    image.onload = null;
    image.onerror = null;
    image.removeAttribute('src');
    document.body.style.overflow = previousBodyOverflow;
    paymentProofZoom = 1;
}

// At 100%, the whole receipt is fitted inside the available image area.
// At higher zoom, only the image area becomes scrollable.
function renderPaymentProof() {
    const image = document.getElementById('paymentProofModalImage');
    if (!image.naturalWidth || !image.naturalHeight) return;
    const viewport = document.getElementById('paymentProofImageContainer');
    const canvas = document.getElementById('paymentProofCanvas');
    const availableWidth = Math.max(1, viewport.clientWidth - 16);
    const availableHeight = Math.max(1, viewport.clientHeight - 16);
    const fit = Math.min(availableWidth / image.naturalWidth,
                         availableHeight / image.naturalHeight);
    const width = Math.round(image.naturalWidth * fit * paymentProofZoom);
    const height = Math.round(image.naturalHeight * fit * paymentProofZoom);

    canvas.style.width = Math.max(viewport.clientWidth, width + 16) + 'px';
    canvas.style.height = Math.max(viewport.clientHeight, height + 16) + 'px';
    image.style.width = width + 'px';
    image.style.height = height + 'px';
    image.style.left = Math.max(8, (Math.max(viewport.clientWidth, width + 16) - width) / 2) + 'px';
    image.style.top = Math.max(8, (Math.max(viewport.clientHeight, height + 16) - height) / 2) + 'px';
    document.getElementById('paymentProofZoomLabel').textContent = Math.round(paymentProofZoom * 100) + '%';
}

function zoomPaymentProof(amount) {
    const viewport = document.getElementById('paymentProofImageContainer');
    const oldWidth = viewport.scrollWidth;
    const oldHeight = viewport.scrollHeight;
    const xFraction = (viewport.scrollLeft + viewport.clientWidth / 2) / Math.max(1, oldWidth);
    const yFraction = (viewport.scrollTop + viewport.clientHeight / 2) / Math.max(1, oldHeight);
    paymentProofZoom = Math.round(Math.min(5, Math.max(0.5, paymentProofZoom + amount)) * 100) / 100;
    renderPaymentProof();
    viewport.scrollLeft = xFraction * viewport.scrollWidth - viewport.clientWidth / 2;
    viewport.scrollTop = yFraction * viewport.scrollHeight - viewport.clientHeight / 2;
}

function resetPaymentProofZoom() {
    paymentProofZoom = 1;
    renderPaymentProof();
    const viewport = document.getElementById('paymentProofImageContainer');
    viewport.scrollLeft = 0;
    viewport.scrollTop = 0;
}

// Wheel zoom is active only while hovering over the modal image area.
const paymentProofViewport = document.getElementById('paymentProofImageContainer');
paymentProofViewport.addEventListener('wheel', function(event) {
    if (document.getElementById('paymentProofModal').classList.contains('hidden')) return;
    event.preventDefault();
    zoomPaymentProof(event.deltaY < 0 ? 0.1 : -0.1);
}, { passive: false });

// Drag the title bar to move the preview, keeping the entire panel on screen.
let proofModalOffsetX = 0;
let proofModalOffsetY = 0;
let proofDragState = null;
const proofDragHandle = document.getElementById('paymentProofDragHandle');
proofDragHandle.addEventListener('pointerdown', function (event) {
    if (event.button !== 0 || event.target.closest('button')) return;
    const panel = document.getElementById('paymentProofWindow');
    event.preventDefault();
    proofDragState = { x: event.clientX, y: event.clientY,
        offsetX: proofModalOffsetX, offsetY: proofModalOffsetY };
    proofDragHandle.setPointerCapture(event.pointerId);
});
proofDragHandle.addEventListener('pointermove', function (event) {
    if (!proofDragState) return;
    const panel = document.getElementById('paymentProofWindow');
    const rect = panel.getBoundingClientRect();
    const nextX = proofDragState.offsetX + event.clientX - proofDragState.x;
    const nextY = proofDragState.offsetY + event.clientY - proofDragState.y;
    const dx = nextX - proofModalOffsetX;
    const dy = nextY - proofModalOffsetY;
    proofModalOffsetX = nextX + Math.max(12 - (rect.left + dx), 0) - Math.max((rect.right + dx) - (window.innerWidth - 12), 0);
    proofModalOffsetY = nextY + Math.max(12 - (rect.top + dy), 0) - Math.max((rect.bottom + dy) - (window.innerHeight - 12), 0);
    panel.style.transform = `translate(${proofModalOffsetX}px, ${proofModalOffsetY}px)`;
});
function stopProofDrag() { proofDragState = null; }
proofDragHandle.addEventListener('pointerup', stopProofDrag);
proofDragHandle.addEventListener('pointercancel', stopProofDrag);
proofDragHandle.addEventListener('lostpointercapture', stopProofDrag);

// Drag the dedicated handle to resize. Clamp size so the panel never leaves the screen.
const proofResizeHandle = document.getElementById('paymentProofResizeHandle');
let proofResizeStart = null;

proofResizeHandle.addEventListener('pointerdown', function(event) {
    if (event.button !== 0) return;
    event.preventDefault();
    event.stopPropagation();
    const panel = document.getElementById('paymentProofWindow');
    proofResizeStart = {
        x: event.clientX,
        y: event.clientY,
        width: panel.getBoundingClientRect().width,
        height: panel.getBoundingClientRect().height,
    };
    proofResizeHandle.setPointerCapture(event.pointerId);
});

proofResizeHandle.addEventListener('pointermove', function(event) {
    if (!proofResizeStart) return;
    const panel = document.getElementById('paymentProofWindow');
    const rect = panel.getBoundingClientRect();
    const maxWidth = Math.max(280, rect.width + window.innerWidth - 12 - rect.right);
    const maxHeight = Math.max(230, rect.height + window.innerHeight - 12 - rect.bottom);
    const width = Math.min(maxWidth, Math.max(280, proofResizeStart.width + event.clientX - proofResizeStart.x));
    const height = Math.min(maxHeight, Math.max(230, proofResizeStart.height + event.clientY - proofResizeStart.y));
    panel.style.width = width + 'px';
    panel.style.height = height + 'px';
});

function stopPaymentProofResize() { proofResizeStart = null; }
proofResizeHandle.addEventListener('pointerup', stopPaymentProofResize);
proofResizeHandle.addEventListener('pointercancel', stopPaymentProofResize);
proofResizeHandle.addEventListener('lostpointercapture', stopPaymentProofResize);

window.addEventListener('resize', () => {
    const panel = document.getElementById('paymentProofWindow');
    panel.style.width = Math.min(panel.getBoundingClientRect().width, window.innerWidth - 24) + 'px';
    panel.style.height = Math.min(panel.getBoundingClientRect().height, window.innerHeight - 24) + 'px';
    if (!document.getElementById('paymentProofModal').classList.contains('hidden')) renderPaymentProof();
});

// Re-fit image when the user drags the modal's resize handle.
const paymentProofResizeObserver = new ResizeObserver(() => {
    if (!document.getElementById('paymentProofModal').classList.contains('hidden')) {
        renderPaymentProof();
    }
});
paymentProofResizeObserver.observe(paymentProofViewport);

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closePaymentProof();
});

</script>

@endsection
