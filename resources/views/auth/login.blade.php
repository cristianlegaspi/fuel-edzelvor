<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | Edzelvor Fuel Trading</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Keep the login page in sync with the dark-mode preference set
        // elsewhere in the app, and avoid a flash of the wrong theme.
        if (localStorage.getItem('darkMode') === 'true' ||
            (localStorage.getItem('darkMode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-800 min-h-screen flex items-center justify-center px-4 transition-colors duration-200">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Edzelvor Fuel Trading" class="h-16 w-16 mx-auto object-contain">
            <h1 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Edzelvor Fuel Trading</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Log in to continue</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-500/10 dark:text-red-400">{{ $errors->first() }}</div>
        @endif

        @if (session('retry_after'))
            <div id="lockoutNotice" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900 dark:bg-amber-500/10 dark:text-amber-400">
                You can try again in <span id="lockoutCountdown" class="font-semibold tabular-nums"></span>.
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 transition-colors duration-200"
              onsubmit="var b=this.querySelector('button[type=submit]'); if (b.disabled) { return false; } b.disabled=true; b.innerHTML='<span class=&quot;btn-spinner&quot;></span> Logging in…';">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500 pr-10">
                    <button type="button" aria-label="Show password" aria-pressed="false"
                            onclick="var i=document.getElementById('password'); var showing=i.type==='text'; i.type=showing?'password':'text'; this.setAttribute('aria-label', showing?'Show password':'Hide password'); this.setAttribute('aria-pressed', String(!showing)); this.querySelector('.eye-open').classList.toggle('hidden', !showing); this.querySelector('.eye-closed').classList.toggle('hidden', showing);"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="eye-open h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3.5c-4.14 0-7.68 2.6-9.06 6.25a.75.75 0 0 0 0 .5C2.32 13.9 5.86 16.5 10 16.5s7.68-2.6 9.06-6.25a.75.75 0 0 0 0-.5C17.68 6.1 14.14 3.5 10 3.5Zm0 10.5a3.75 3.75 0 1 1 0-7.5 3.75 3.75 0 0 1 0 7.5Z" />
                            <path d="M10 8.25a1.75 1.75 0 1 0 0 3.5 1.75 1.75 0 0 0 0-3.5Z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="eye-closed hidden h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.86-1.86c1.73-1.28 3.05-3.06 3.78-5.13a.75.75 0 0 0 0-.5C17.68 6.1 14.14 3.5 10 3.5c-1.7 0-3.27.43-4.62 1.19L3.28 2.22Zm5.29 5.29 1.62 1.62a1.75 1.75 0 0 1 2.15 2.15l1.62 1.62a3.75 3.75 0 0 0-5.39-5.39Z" clip-rule="evenodd" />
                            <path d="M2.22 4.34 4.7 6.82C3.4 7.87 2.4 9.24 1.85 10.5a.75.75 0 0 0 0 .5c1.32 3.15 4.4 5.5 8.15 5.5.99 0 1.94-.16 2.82-.46l2.66 2.66a.75.75 0 0 0 1.06-1.06L3.28 3.28a.75.75 0 0 0-1.06 1.06Z" />
                        </svg>
                    </button>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember"> Remember me</label>
            <button type="submit" id="submitBtn" data-label="Log In" class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-not-allowed transition-colors duration-200">Log In</button>
        </form>
    </div>
    <style>
        .btn-spinner {
            display: inline-block; width: 1em; height: 1em;
            border: 2px solid currentColor; border-right-color: transparent;
            border-radius: 50%; animation: btn-spin 0.6s linear infinite; vertical-align: -0.2em;
        }
        @keyframes btn-spin { to { transform: rotate(360deg); } }
    </style>

    @if (session('retry_after'))
        <script>
            (function () {
                var remaining = {{ (int) session('retry_after') }};
                var countdownEl = document.getElementById('lockoutCountdown');
                var noticeEl = document.getElementById('lockoutNotice');
                var submitBtn = document.getElementById('submitBtn');

                function format(seconds) {
                    var m = Math.floor(seconds / 60);
                    var s = seconds % 60;
                    return m + ':' + (s < 10 ? '0' : '') + s;
                }

                function render() {
                    countdownEl.textContent = format(remaining);
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Try again in ' + format(remaining);
                }

                render();
                submitBtn.disabled = remaining > 0;

                var timer = setInterval(function () {
                    remaining -= 1;

                    if (remaining <= 0) {
                        clearInterval(timer);
                        noticeEl.remove();
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.label;
                        return;
                    }

                    render();
                }, 1000);
            })();
        </script>
    @endif
</body>
</html>