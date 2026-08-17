@props(['label', 'value', 'sublabel' => null, 'color' => 'text-slate-900 dark:text-white'])

<div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="mt-2 text-xl sm:text-2xl font-bold {{ $color }} break-words">{{ $value }}</p>
    @if($sublabel)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $sublabel }}</p>@endif
</div>
