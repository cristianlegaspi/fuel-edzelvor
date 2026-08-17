<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Edzelvor Fuel Trading</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Set dark mode before paint to avoid flash of wrong theme
        if (localStorage.getItem('darkMode') === 'true' ||
            (localStorage.getItem('darkMode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        /* Smooth theme + hover transitions app-wide */
        html { color-scheme: light; }
        html.dark { color-scheme: dark; }
        *, *::before, *::after {
            transition-property: background-color, border-color, color, box-shadow, opacity;
            transition-duration: 150ms;
            transition-timing-function: ease-in-out;
        }
        /* Don't fight transforms/animations that need to be instant or on their own timing */
        .btn-spinner, #page-loader, [x-cloak] {
            transition: none;
        }

        /* Top page-loading bar */
        #page-loader {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            z-index: 9999;
            background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
            background-size: 200% 100%;
            transform: scaleX(0);
            transform-origin: left;
            opacity: 0;
            transition: transform 300ms ease-out, opacity 150ms ease-out;
        }
        #page-loader.is-active {
            transform: scaleX(1);
            opacity: 1;
            animation: page-loader-shimmer 1.1s linear infinite;
        }
        @keyframes page-loader-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Inline button spinner used while a form is submitting */
        .btn-spinner {
            display: inline-block;
            width: 1em; height: 1em;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: btn-spin 0.6s linear infinite;
            vertical-align: -0.2em;
        }
        @keyframes btn-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased dark:bg-slate-900 dark:text-slate-100"
      x-data="{
          sidebarOpen: false,
          collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
          dark: document.documentElement.classList.contains('dark'),
          toggleDark() {
              this.dark = !this.dark;
              document.documentElement.classList.toggle('dark', this.dark);
              localStorage.setItem('darkMode', this.dark);
          },
          toggleCollapsed() {
              this.collapsed = !this.collapsed;
              localStorage.setItem('sidebarCollapsed', this.collapsed);
          }
      }">
<div id="page-loader"></div>

@auth
    {{-- Idle-timeout warning modal. The countdown and the actual logout
         are both driven by idle-timeout.js below; this is just markup. --}}
    <div id="idle-warning-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 px-4">
        <div class="w-full max-w-sm rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 text-center">
            <div class="mx-auto mb-3 h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <x-icon name="logout" />
            </div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Still there?</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                You've been inactive. For your security, you'll be logged out in
                <span id="idle-warning-seconds" class="font-semibold text-slate-700 dark:text-slate-200">60</span> seconds.
            </p>
            <button id="idle-stay-logged-in" class="mt-4 w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                Stay Logged In
            </button>
        </div>
    </div>
    <form id="idle-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
@endauth

<div class="flex min-h-screen">

    <!-- Mobile overlay -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
         x-cloak></div>

    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:w-20' : 'lg:w-72'
        ]"
        class="fixed inset-y-0 left-0 z-40 w-72 shrink-0 border-r border-slate-200 bg-white flex flex-col
               transition-all duration-200 ease-in-out
               lg:static lg:translate-x-0
               dark:border-slate-800 dark:bg-slate-900">

        <div class="flex items-center gap-3 px-6 py-6 border-b border-slate-100 dark:border-slate-800"
             :class="{ 'lg:justify-center lg:px-2': collapsed }">
            <div class="h-10 w-10 shrink-0 rounded-xl bg-amber-500 flex items-center justify-center text-white font-bold">F</div>
            <div x-show="!collapsed || sidebarOpen" x-transition.opacity>
                <p class="font-bold text-slate-900 leading-tight dark:text-white">Edzelvor Fuel Trading</p>
                <p class="text-xs titlecase tracking-wide text-slate-400 dark:text-slate-500">Management System</p>
            </div>
            <button @click="sidebarOpen = false" class="ml-auto lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-6 space-y-1">
            <p x-show="!collapsed || sidebarOpen" class="px-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 dark:text-slate-500">Menu</p>

            <a href="{{ route('fuel-dashboard') }}" :title="collapsed ? 'Dashboard' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-dashboard') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="grid" /> <span x-show="!collapsed || sidebarOpen">Dashboard</span>
            </a>

            <p x-show="!collapsed || sidebarOpen" class="px-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 dark:text-slate-500">Customer Reports</p>
            <a href="{{ route('fuel-customer-purchases.index') }}" :title="collapsed ? 'Customer Purchases' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-customer-purchases.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="cart" /> <span x-show="!collapsed || sidebarOpen">Customer Purchases</span>
            </a>
            <a href="{{ route('fuel-customer-payments.index') }}" :title="collapsed ? 'Customer Payments' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-customer-payments.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="cart" /> <span x-show="!collapsed || sidebarOpen">Customer Payments</span>
            </a>
            <a href="{{ route('fuel-customer-net-income.index') }}" :title="collapsed ? 'Customer Net Income' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-customer-net-income.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="cart" /> <span x-show="!collapsed || sidebarOpen">Customer Net Income</span>
            </a>

            <p x-show="!collapsed || sidebarOpen" class="px-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 dark:text-slate-500">Supplier Reports</p>
            <a href="{{ route('fuel-sales-orders.index') }}" :title="collapsed ? 'Supplier Orders' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-sales-orders.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="truck" /> <span x-show="!collapsed || sidebarOpen">Supplier Orders</span>
            </a>
            <a href="{{ route('fuel-payments.index') }}" :title="collapsed ? 'Supplier Payments' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-payments.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="truck" /> <span x-show="!collapsed || sidebarOpen">Supplier Payments</span>
            </a>

            <p x-show="!collapsed || sidebarOpen" class="px-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 dark:text-slate-500">Tanker Reports</p>
            <a href="{{ route('fuel-tanker-records.index') }}" :title="collapsed ? 'Tanker Purchases' : ''"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('fuel-tanker-records.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
               :class="{ 'lg:justify-center': collapsed }">
                <x-icon name="truck" /> <span x-show="!collapsed || sidebarOpen">Tanker Purchases</span>
            </a>

            @if(auth()->user()?->isSuperAdmin())
                <p x-show="!collapsed || sidebarOpen" class="px-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 dark:text-slate-500">Administration</p>
                <a href="{{ route('users.index') }}" :title="collapsed ? 'User Management' : ''"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
                   :class="{ 'lg:justify-center': collapsed }">
                    <x-icon name="users" /> <span x-show="!collapsed || sidebarOpen">User Management</span>
                </a>
            @endif
        </nav>

        <div class="border-t border-slate-100 px-4 py-4 space-y-1 dark:border-slate-800">
            <!-- Desktop collapse toggle -->
            <button @click="toggleCollapsed()" class="hidden lg:flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                    :class="{ 'justify-center': collapsed }">
                <x-icon name="chevrons-left" x-show="!collapsed" x-cloak />
                <x-icon name="chevrons-right" x-show="collapsed" x-cloak />
                <span x-show="!collapsed">Collapse</span>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                        :class="{ 'lg:justify-center': collapsed }">
                    <x-icon name="logout" /> <span x-show="!collapsed || sidebarOpen">Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-8 py-4 lg:py-6 border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = true" class="lg:hidden shrink-0 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <x-icon name="menu" />
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-2xl font-bold text-slate-900 truncate dark:text-white">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5 truncate dark:text-slate-400">@yield('page-subtitle', '')</p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @yield('header-actions')
                <button @click="toggleDark()" class="h-9 w-9 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:text-amber-400 dark:hover:bg-slate-800">
                    <x-icon name="moon" />
                </button>
                <div class="hidden sm:block text-right leading-tight">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ \App\Models\User::ROLES[auth()->user()->role] ?? ucfirst(auth()->user()->role) }}</p>
                </div>
                <div class="h-9 w-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-semibold dark:bg-amber-500/20 dark:text-amber-400"
                     title="{{ auth()->user()->name }} ({{ \App\Models\User::ROLES[auth()->user()->role] ?? ucfirst(auth()->user()->role) }})">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 overflow-x-hidden">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-500/10 dark:text-red-400">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Swal === 'undefined') return;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Swal === 'undefined') return;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Please check the form for errors',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
        });
    </script>
@endif

<script>
    // ------------------------------------------------------------------
    // SweetAlert2 confirmation before create / update / delete submits.
    //
    // Any <form> can opt in with:
    //   data-confirm="Question to show the user"
    // Optional:
    //   data-confirm-type="danger"           -> red "delete" styling (defaults to amber "save" styling)
    //   data-confirm-title="Custom title"
    //   data-confirm-button="Custom label"
    //
    // Runs (capture phase) before the page-loader script below, so a
    // cancelled confirmation never triggers the loading bar/spinner —
    // only a confirmed submission does.
    // ------------------------------------------------------------------
    (function () {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (!form.hasAttribute('data-confirm')) return;
            if (form.dataset.confirmed === '1') return; // already confirmed once, let it through

            if (typeof Swal === 'undefined') return; // library failed to load — fall back to normal submit

            e.preventDefault();
            e.stopImmediatePropagation();

            const isDanger = form.dataset.confirmType === 'danger';
            const submitter = e.submitter;

            Swal.fire({
                title: form.dataset.confirmTitle || (isDanger ? 'Are you sure?' : 'Please confirm'),
                text: form.dataset.confirm,
                icon: isDanger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: form.dataset.confirmButton || (isDanger ? 'Yes, delete it' : 'Yes, save it'),
                cancelButtonText: 'Cancel',
                confirmButtonColor: isDanger ? '#dc2626' : '#f59e0b',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;

                form.dataset.confirmed = '1';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter || undefined);
                } else {
                    form.submit();
                }
            });
        }, true);
    })();
</script>

<script>
    // ------------------------------------------------------------------
    // Global "smooth" navigation + submit loader.
    // Shows a top progress bar and puts a spinner on the clicked submit
    // button whenever a form is submitted or an internal link is
    // followed, so actions like "add order", "save payment", etc. always
    // give instant visual feedback instead of feeling stuck/frozen.
    //
    // Opt out per-form/link with the `data-no-loader` attribute.
    // ------------------------------------------------------------------
    (function () {
        const bar = document.getElementById('page-loader');
        let hideTimer = null;

        function showLoader() {
            clearTimeout(hideTimer);
            bar.classList.add('is-active');
        }
        function hideLoader() {
            bar.classList.remove('is-active');
        }

        function spinnerButton(btn) {
            if (!btn || btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-wait');
            btn.innerHTML = '<span class="btn-spinner"></span> <span>' +
                (btn.dataset.loadingText || 'Please wait…') + '</span>';
        }

        // Forms: sales orders, customer purchases, payments, tanker records, etc.
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (e.defaultPrevented) return; // e.g. a confirm() dialog was cancelled
            if (form.hasAttribute('data-no-loader')) return;

            showLoader();

            const submitter = e.submitter || form.querySelector('button[type="submit"], button:not([type])');
            spinnerButton(submitter);

            // Safety net: if navigation is somehow interrupted, don't leave
            // the UI stuck forever.
            hideTimer = setTimeout(hideLoader, 15000);
        }, true);

        // Internal link navigation (sidebar, "View all", pagination, etc.)
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            if (link.hasAttribute('data-no-loader')) return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;
            if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

            showLoader();
            hideTimer = setTimeout(hideLoader, 15000);
        }, true);

        // Hide the bar once the next page has actually painted, and
        // restore any stuck buttons if the page is served from bfcache.
        window.addEventListener('pageshow', function () {
            hideLoader();
            document.querySelectorAll('[data-loading="1"]').forEach(function (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-wait');
                if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.loading;
            });
        });
    })();
</script>

@auth
<script>
    // ------------------------------------------------------------------
    // Auto logout on inactivity (client-side half — see
    // App\Http\Middleware\AutoLogoutOnInactivity for the server-side
    // enforcement, which is what actually protects the account if JS
    // is disabled or the tab is put to sleep).
    //
    // Flow: any user activity resets the idle timer. If the timer runs
    // out with WARNING_SECONDS left, a modal counts down; any further
    // activity (or clicking "Stay Logged In") cancels it. If the
    // countdown reaches zero, the user is logged out automatically.
    // ------------------------------------------------------------------
    (function () {
        const IDLE_TIMEOUT_MS = {{ (int) config('session.idle_timeout', 15) * 60 * 1000 }};
        const WARNING_SECONDS = 60;
        const PING_URL = "{{ route('ping') }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";

        if (IDLE_TIMEOUT_MS <= 0) return;

        const modal = document.getElementById('idle-warning-modal');
        const secondsEl = document.getElementById('idle-warning-seconds');
        const stayBtn = document.getElementById('idle-stay-logged-in');
        const logoutForm = document.getElementById('idle-logout-form');

        let idleTimer = null;
        let countdownTimer = null;
        let secondsLeft = WARNING_SECONDS;
        let lastPing = 0;

        function doLogout() {
            logoutForm.submit();
        }

        function showWarning() {
            secondsLeft = WARNING_SECONDS;
            secondsEl.textContent = secondsLeft;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            countdownTimer = setInterval(function () {
                secondsLeft -= 1;
                secondsEl.textContent = secondsLeft;
                if (secondsLeft <= 0) {
                    clearInterval(countdownTimer);
                    doLogout();
                }
            }, 1000);
        }

        function hideWarning() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            clearInterval(countdownTimer);
        }

        function pingServer() {
            const now = Date.now();
            if (now - lastPing < 30000) return; // throttle to once/30s
            lastPing = now;
            fetch(PING_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true,
            }).catch(function () { /* connectivity hiccup - not fatal */ });
        }

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            if (!modal.classList.contains('hidden')) {
                hideWarning();
            }
            pingServer();
            idleTimer = setTimeout(showWarning, Math.max(IDLE_TIMEOUT_MS - WARNING_SECONDS * 1000, 0));
        }

        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
            document.addEventListener(evt, resetIdleTimer, { passive: true });
        });

        stayBtn.addEventListener('click', resetIdleTimer);

        resetIdleTimer();
    })();
</script>
@endauth

@stack('scripts')
</body>
</html>