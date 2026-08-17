@props(['color' => 'gray'])

@php
$colors = [
    'gray'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    'yellow' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
    'blue'   => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    'green'  => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    'red'    => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold '.($colors[$color] ?? $colors['gray'])]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $slot }}
</span>
