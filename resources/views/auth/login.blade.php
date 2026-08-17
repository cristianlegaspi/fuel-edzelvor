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
            <div class="h-12 w-12 rounded-xl bg-amber-500 mx-auto flex items-center justify-center text-white font-bold text-lg">F</div>
            <h1 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Edzelvor Fuel Trading</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Log in to continue</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-500/10 dark:text-red-400">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 transition-colors duration-200"
              onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.innerHTML='<span class=&quot;btn-spinner&quot;></span> Logging in…';">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember"> Remember me</label>
            <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Log In</button>
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
</body>
</html>
