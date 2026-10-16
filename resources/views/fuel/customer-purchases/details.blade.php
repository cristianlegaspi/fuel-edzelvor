
@extends('layouts.fuel')

@section('title', 'Customer Purchase Details')
@section('page-title', 'Customer Purchase Details')
@section('page-subtitle', $purchase->sales_order_no)

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">

        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ $purchase->customer }}
            </h2>

            <p class="text-sm text-slate-500">
                Sales Order: {{ $purchase->sales_order_no }}
            </p>
        </div>

        <div class="flex items-center gap-3">

            <x-badge :color="match($purchase->status) {
                'paid' => 'green',
                'partial' => 'yellow',
                'overpaid' => 'blue',
                default => 'red'
            }">
                {{ ucfirst($purchase->status) }}
            </x-badge>

            @if($purchase->status === 'paid')
                <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm text-slate-500">
                    🔒 Locked
                </span>
            @endif

            <a
                href="{{ route('fuel-customer-purchases.index') }}"
                class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm text-slate-600 dark:text-slate-300"
            >
                Back
            </a>

        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <x-stat-card
            label="Total Liters"
            :value="number_format($purchase->total_liters, 2).' L'"
        />

        <x-stat-card
            label="Total Payables"
            :value="'₱'.number_format($purchase->total_payables, 2)"
        />

        <x-stat-card
            label="Net Income"
            :value="'₱'.number_format($purchase->net_income, 2)"
        />

    </div>

    {{-- Fuel Products --}}
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">

        <h3 class="mb-4 font-semibold text-slate-900 dark:text-white">
            Fuel Products
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="border-b border-slate-200 dark:border-slate-700 text-left text-slate-500">
                    <tr>
                        <th class="py-3 px-2">Product</th>
                        <th class="py-3 px-2">Liters</th>
                        <th class="py-3 px-2">Price / Liter</th>
                        <th class="py-3 px-2">Line Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                    @forelse($purchase->items as $item)
                        <tr>
                            <td class="py-3 px-2">
                                {{ $item->fuel_product ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                {{ number_format($item->liters ?? 0, 2) }} L
                            </td>

                            <td class="py-3 px-2">
                                ₱{{ number_format($item->price_per_liter ?? 0, 2) }}
                            </td>

                            <td class="py-3 px-2">
                                ₱{{ number_format($item->line_total ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-400">
                                No fuel products recorded.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment History --}}
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">

        <h3 class="mb-4 font-semibold text-slate-900 dark:text-white">
            Customer Payment History
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="border-b border-slate-200 dark:border-slate-700 text-left text-slate-500">
                    <tr>
                        <th class="py-3 px-2">Date</th>
                        <th class="py-3 px-2">Tracking No.</th>
                        <th class="py-3 px-2">Amount</th>
                        <th class="py-3 px-2">Method</th>
                        <th class="py-3 px-2">Proof</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                    @forelse($purchase->payments as $payment)
                        <tr>
                            <td class="py-3 px-2">
                                {{ optional($payment->payment_date)->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                {{ $payment->customer_payment_tracking_no ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                ₱{{ number_format($payment->amount, 2) }}
                            </td>

                            <td class="py-3 px-2">
                                {{ $payment->payment_method ?? '—' }}
                            </td>

                            <td class="py-3 px-2">
                                @if($payment->proof_image)
                                   <button
                                        type="button"
                                        onclick="openPaymentProof(@js(route('fuel-customer-payments.proof', $payment)))"
                                        class="text-amber-600 dark:text-amber-400 font-medium hover:underline"
                                    >
                                        View Proof
                                    </button>
                                @else
                                    <span class="text-slate-400">No Image</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-400">
                                No payments recorded.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

</div>


{{-- Draggable / Resizable Proof of Payment Modal --}}
<div
    id="paymentProofModal"
    class="hidden fixed inset-0 z-[9999] bg-black/65"
    role="dialog"
    aria-modal="true"
    aria-label="Proof of Payment"
    onclick="if(event.target === this) closePaymentProof()"
>
    <div
        id="paymentProofWindow"
        class="fixed flex flex-col overflow-hidden rounded-xl bg-white dark:bg-slate-900 shadow-2xl"
        style="left:50%;top:50%;width:440px;height:570px;max-width:calc(100vw - 24px);max-height:calc(100dvh - 24px);transform:translate(-50%,-50%);"
    >

        {{-- Draggable Header --}}
        <div
            id="paymentProofDragHandle"
            class="flex shrink-0 cursor-move touch-none select-none items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 px-3 py-2"
            style="touch-action:none;"
        >
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                Proof of Payment
            </h3>

            <div class="flex items-center gap-1">
                <button type="button"
                    onclick="zoomPaymentProof(-0.2)"
                    class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm"
                    title="Zoom Out">−</button>

                <span id="paymentProofZoomLabel"
                    class="w-12 text-center text-xs text-slate-500 dark:text-slate-300">
                    100%
                </span>

                <button type="button"
                    onclick="zoomPaymentProof(0.2)"
                    class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm"
                    title="Zoom In">+</button>

                <button type="button"
                    onclick="resetPaymentProofZoom()"
                    class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-xs">
                    Fit
                </button>

                <button type="button"
                    onclick="closePaymentProof()"
                    class="ml-1 rounded px-2 py-1 text-xl text-slate-500 hover:text-red-600"
                    aria-label="Close">
                    &times;
                </button>
            </div>
        </div>

        {{-- Image Viewer --}}
        <div
            id="paymentProofImageContainer"
            class="min-h-0 flex-1 overflow-auto bg-slate-100 dark:bg-slate-950"
            style="overscroll-behavior:contain;"
        >
            <div id="paymentProofCanvas"
                class="relative"
                style="min-width:100%;min-height:100%;">

                <img
                    id="paymentProofModalImage"
                    alt="Proof of Payment"
                    draggable="false"
                    class="absolute block rounded-sm"
                    style="max-width:none;max-height:none;user-select:none;"
                >
            </div>
        </div>

        {{-- Footer / Resize Handle --}}
        <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 px-3 py-2">

            <span class="text-xs text-slate-400">
                Mouse wheel: Zoom · Drag header: Move
            </span>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="closePaymentProof()"
                    class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white hover:bg-slate-700"
                >
                    Close
                </button>

                <div
                    id="paymentProofResizeHandle"
                    title="Drag to resize"
                    class="flex h-7 w-7 cursor-nwse-resize touch-none select-none items-end justify-end rounded border border-slate-300 dark:border-slate-600 p-1 text-slate-500"
                    style="touch-action:none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="17" height="17"
                        viewBox="0 0 24 24"
                        fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round">
                        <path d="M21 15l-6 6M21 9L9 21"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('paymentProofModal');
    const panel = document.getElementById('paymentProofWindow');
    const header = document.getElementById('paymentProofDragHandle');
    const handle = document.getElementById('paymentProofResizeHandle');
    const viewport = document.getElementById('paymentProofImageContainer');
    const canvas = document.getElementById('paymentProofCanvas');
    const image = document.getElementById('paymentProofModalImage');
    const label = document.getElementById('paymentProofZoomLabel');

    let zoom = 1;
    let originalOverflow = '';
    let drag = null;
    let resize = null;

    const clamp = (value, min, max) =>
        Math.min(Math.max(value, min), Math.max(min, max));

    function fitImage() {
        if (!image.naturalWidth || !image.naturalHeight) return;

        const vw = viewport.clientWidth;
        const vh = viewport.clientHeight;

        const fit = Math.min(
            Math.max(1, vw - 16) / image.naturalWidth,
            Math.max(1, vh - 16) / image.naturalHeight
        );

        const width = Math.max(1, image.naturalWidth * fit * zoom);
        const height = Math.max(1, image.naturalHeight * fit * zoom);

        const cw = Math.max(vw, width + 16);
        const ch = Math.max(vh, height + 16);

        canvas.style.width = cw + 'px';
        canvas.style.height = ch + 'px';

        image.style.width = width + 'px';
        image.style.height = height + 'px';
        image.style.left = (cw - width) / 2 + 'px';
        image.style.top = (ch - height) / 2 + 'px';

        label.textContent = Math.round(zoom * 100) + '%';
    }

    window.openPaymentProof = function (url) {
        const width = Math.min(440, window.innerWidth - 24);
        const height = Math.min(570, window.innerHeight - 24);

        panel.style.width = width + 'px';
        panel.style.height = height + 'px';
        panel.style.left = (window.innerWidth - width) / 2 + 'px';
        panel.style.top = (window.innerHeight - height) / 2 + 'px';
        panel.style.transform = 'none';

        zoom = 1;
        label.textContent = '100%';

        image.onload = fitImage;
        image.onerror = function () {
            alert('Unable to load payment proof image.');
            closePaymentProof();
        };

        modal.classList.remove('hidden');
        originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        viewport.scrollLeft = 0;
        viewport.scrollTop = 0;

        image.src = url;
        if (image.complete && image.naturalWidth) fitImage();
    };

    window.closePaymentProof = function () {
        if (modal.classList.contains('hidden')) return;

        modal.classList.add('hidden');

        image.onload = null;
        image.onerror = null;
        image.removeAttribute('src');

        document.body.style.overflow = originalOverflow;
        zoom = 1;
    };

    window.zoomPaymentProof = function (step) {
        if (!image.naturalWidth) return;

        const previousW = Math.max(1, viewport.scrollWidth);
        const previousH = Math.max(1, viewport.scrollHeight);

        const x = (viewport.scrollLeft + viewport.clientWidth / 2) / previousW;
        const y = (viewport.scrollTop + viewport.clientHeight / 2) / previousH;

        zoom = Math.round(clamp(zoom + step, 0.5, 5) * 100) / 100;
        fitImage();

        viewport.scrollLeft = x * viewport.scrollWidth - viewport.clientWidth / 2;
        viewport.scrollTop = y * viewport.scrollHeight - viewport.clientHeight / 2;
    };

    window.resetPaymentProofZoom = function () {
        zoom = 1;
        fitImage();
        viewport.scrollLeft = 0;
        viewport.scrollTop = 0;
    };

    viewport.addEventListener('wheel', function (event) {
        if (modal.classList.contains('hidden')) return;
        event.preventDefault();
        zoomPaymentProof(event.deltaY < 0 ? 0.1 : -0.1);
    }, { passive: false });

    // Drag header to move the modal
    header.addEventListener('pointerdown', function (event) {
        if (event.button !== 0 || event.target.closest('button')) return;

        event.preventDefault();

        const rect = panel.getBoundingClientRect();
        drag = {
            pointerX: event.clientX,
            pointerY: event.clientY,
            left: rect.left,
            top: rect.top
        };

        header.setPointerCapture(event.pointerId);
    });

    header.addEventListener('pointermove', function (event) {
        if (!drag) return;

        panel.style.left = clamp(
            drag.left + event.clientX - drag.pointerX,
            12,
            window.innerWidth - panel.offsetWidth - 12
        ) + 'px';

        panel.style.top = clamp(
            drag.top + event.clientY - drag.pointerY,
            12,
            window.innerHeight - panel.offsetHeight - 12
        ) + 'px';
    });

    const stopDrag = () => { drag = null; };
    header.addEventListener('pointerup', stopDrag);
    header.addEventListener('pointercancel', stopDrag);
    header.addEventListener('lostpointercapture', stopDrag);

    // Bottom-right resize handle
    handle.addEventListener('pointerdown', function (event) {
        if (event.button !== 0) return;

        event.preventDefault();
        event.stopPropagation();

        const rect = panel.getBoundingClientRect();

        resize = {
            pointerX: event.clientX,
            pointerY: event.clientY,
            width: rect.width,
            height: rect.height,
            left: rect.left,
            top: rect.top
        };

        handle.setPointerCapture(event.pointerId);
    });

    handle.addEventListener('pointermove', function (event) {
        if (!resize) return;

        panel.style.width = clamp(
            resize.width + event.clientX - resize.pointerX,
            Math.min(280, window.innerWidth - 24),
            window.innerWidth - resize.left - 12
        ) + 'px';

        panel.style.height = clamp(
            resize.height + event.clientY - resize.pointerY,
            Math.min(230, window.innerHeight - 24),
            window.innerHeight - resize.top - 12
        ) + 'px';

        fitImage();
    });

    const stopResize = () => { resize = null; };
    handle.addEventListener('pointerup', stopResize);
    handle.addEventListener('pointercancel', stopResize);
    handle.addEventListener('lostpointercapture', stopResize);

    window.addEventListener('resize', function () {
        if (modal.classList.contains('hidden')) return;

        const rect = panel.getBoundingClientRect();

        const width = Math.min(rect.width, window.innerWidth - 24);
        const height = Math.min(rect.height, window.innerHeight - 24);

        panel.style.width = width + 'px';
        panel.style.height = height + 'px';
        panel.style.left = clamp(rect.left, 12, window.innerWidth - width - 12) + 'px';
        panel.style.top = clamp(rect.top, 12, window.innerHeight - height - 12) + 'px';

        fitImage();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closePaymentProof();
    });
})();
</script>


@endsection
