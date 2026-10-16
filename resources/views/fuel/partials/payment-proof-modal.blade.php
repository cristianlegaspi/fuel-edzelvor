
{{-- Reusable Proof of Payment Modal --}}
<div id="paymentProofModal"
     class="hidden fixed inset-0 z-[9999] bg-black/65"
     role="dialog"
     aria-modal="true"
     aria-label="Proof of Payment"
     onclick="if(event.target === this) closePaymentProof()">

    <div id="paymentProofWindow"
         class="fixed flex flex-col overflow-hidden rounded-xl bg-white dark:bg-slate-900 shadow-2xl"
         style="left:50%;top:50%;width:440px;height:570px;max-width:calc(100vw - 24px);max-height:calc(100dvh - 24px);transform:translate(-50%,-50%);">

        {{-- Draggable Header --}}
        <div id="paymentProofDragHandle"
             class="flex shrink-0 cursor-move touch-none select-none items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 px-3 py-2">

            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                Proof of Payment
            </h3>

            <div class="flex items-center gap-1">
                <button type="button" onclick="zoomPaymentProof(-0.2)"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm">−</button>

                <span id="paymentProofZoomLabel"
                      class="w-12 text-center text-xs text-slate-500 dark:text-slate-300">
                    100%
                </span>

                <button type="button" onclick="zoomPaymentProof(0.2)"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-sm">+</button>

                <button type="button" onclick="resetPaymentProofZoom()"
                        class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-xs">
                    Fit
                </button>

                <button type="button" onclick="closePaymentProof()"
                        class="ml-1 rounded px-2 py-1 text-xl text-slate-500 hover:text-red-600">
                    &times;
                </button>
            </div>
        </div>

        {{-- Image Viewer --}}
        <div id="paymentProofImageContainer"
             class="min-h-0 flex-1 overflow-auto bg-slate-100 dark:bg-slate-950"
             style="overscroll-behavior:contain;">

            <div id="paymentProofCanvas"
                 class="relative"
                 style="min-width:100%;min-height:100%;">

                <img id="paymentProofModalImage"
                     alt="Proof of Payment"
                     draggable="false"
                     class="absolute block rounded-sm"
                     style="max-width:none;max-height:none;user-select:none;">
            </div>
        </div>

        {{-- Footer / Resize Handle --}}
        <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 px-3 py-2">

            <span class="text-xs text-slate-400">
                Scroll: Zoom · Drag header: Move
            </span>

            <div class="flex items-center gap-2">
                <button type="button" onclick="closePaymentProof()"
                        class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-white hover:bg-slate-700">
                    Close
                </button>

                <div id="paymentProofResizeHandle"
                     title="Drag to resize"
                     class="flex h-7 w-7 cursor-nwse-resize touch-none select-none items-end justify-end rounded border border-slate-300 dark:border-slate-600 p-1 text-slate-500">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="17" height="17" viewBox="0 0 24 24"
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
    const zoomLabel = document.getElementById('paymentProofZoomLabel');

    let zoom = 1;
    let drag = null;
    let resize = null;
    let originalOverflow = '';

    const clamp = (v, min, max) =>
        Math.min(Math.max(v, min), Math.max(min, max));

    function renderImage() {
        if (!image.naturalWidth || !image.naturalHeight) return;

        const vw = viewport.clientWidth;
        const vh = viewport.clientHeight;
        const fit = Math.min(
            Math.max(1, vw - 16) / image.naturalWidth,
            Math.max(1, vh - 16) / image.naturalHeight
        );

        const width = image.naturalWidth * fit * zoom;
        const height = image.naturalHeight * fit * zoom;
        const cw = Math.max(vw, width + 16);
        const ch = Math.max(vh, height + 16);

        canvas.style.width = cw + 'px';
        canvas.style.height = ch + 'px';
        image.style.width = width + 'px';
        image.style.height = height + 'px';
        image.style.left = (cw - width) / 2 + 'px';
        image.style.top = (ch - height) / 2 + 'px';

        zoomLabel.textContent = Math.round(zoom * 100) + '%';
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
        zoomLabel.textContent = '100%';

        image.onload = renderImage;
        image.onerror = function () {
            window.closePaymentProof();
            alert('Unable to load payment proof image.');
        };

        modal.classList.remove('hidden');
        originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        viewport.scrollLeft = 0;
        viewport.scrollTop = 0;

        image.removeAttribute('src');
        image.src = url;

        if (image.complete && image.naturalWidth) renderImage();
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
        zoom = Math.round(clamp(zoom + step, 0.5, 5) * 100) / 100;
        renderImage();
    };

    window.resetPaymentProofZoom = function () {
        zoom = 1;
        renderImage();
        viewport.scrollLeft = 0;
        viewport.scrollTop = 0;
    };

    viewport.addEventListener('wheel', function (event) {
        if (modal.classList.contains('hidden')) return;
        event.preventDefault();
        window.zoomPaymentProof(event.deltaY < 0 ? 0.1 : -0.1);
    }, { passive: false });

    // Move modal by dragging header
    header.addEventListener('pointerdown', function (event) {
        if (event.button !== 0 || event.target.closest('button')) return;
        event.preventDefault();

        const rect = panel.getBoundingClientRect();
        drag = {
            x: event.clientX,
            y: event.clientY,
            left: rect.left,
            top: rect.top
        };

        header.setPointerCapture(event.pointerId);
    });

    header.addEventListener('pointermove', function (event) {
        if (!drag) return;

        panel.style.left = clamp(
            drag.left + event.clientX - drag.x,
            0,
            window.innerWidth - panel.offsetWidth
        ) + 'px';

        panel.style.top = clamp(
            drag.top + event.clientY - drag.y,
            0,
            window.innerHeight - panel.offsetHeight
        ) + 'px';
    });

    function stopDrag() { drag = null; }
    header.addEventListener('pointerup', stopDrag);
    header.addEventListener('pointercancel', stopDrag);
    header.addEventListener('lostpointercapture', stopDrag);

    // Resize modal using bottom-right handle
    handle.addEventListener('pointerdown', function (event) {
        if (event.button !== 0) return;
        event.preventDefault();

        const rect = panel.getBoundingClientRect();
        resize = {
            x: event.clientX,
            y: event.clientY,
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
            resize.width + event.clientX - resize.x,
            Math.min(280, window.innerWidth - 24),
            window.innerWidth - resize.left
        ) + 'px';

        panel.style.height = clamp(
            resize.height + event.clientY - resize.y,
            Math.min(230, window.innerHeight - 24),
            window.innerHeight - resize.top
        ) + 'px';

        renderImage();
    });

    function stopResize() { resize = null; }
    handle.addEventListener('pointerup', stopResize);
    handle.addEventListener('pointercancel', stopResize);
    handle.addEventListener('lostpointercapture', stopResize);

    window.addEventListener('resize', function () {
        if (modal.classList.contains('hidden')) return;

        const width = Math.min(panel.offsetWidth, window.innerWidth - 24);
        const height = Math.min(panel.offsetHeight, window.innerHeight - 24);

        panel.style.width = width + 'px';
        panel.style.height = height + 'px';
        panel.style.left = clamp(panel.offsetLeft, 0, window.innerWidth - width) + 'px';
        panel.style.top = clamp(panel.offsetTop, 0, window.innerHeight - height) + 'px';

        renderImage();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') window.closePaymentProof();
    });
})();
</script>
