@extends('layouts.fuel')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')
@section('page-subtitle', 'Who did what, and when')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
           class="w-full sm:w-64 rounded-lg border-slate-300 dark:border-slate-600 text-sm">

    <select name="action" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <option value="">All actions</option>
        @foreach($actions as $value => $label)
            <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="user_id" class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <option value="">All users</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
        @endforeach
    </select>

    <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        <span>to</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    </div>

    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">
        Filter
    </button>

    @if(request()->hasAny(['search', 'action', 'user_id', 'date_from', 'date_to']))
        <a href="{{ route('audit-logs.index') }}" class="text-sm font-medium text-slate-500 hover:underline dark:text-slate-400">
            Clear filters
        </a>
    @endif
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">When</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">User</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Action</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Details</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">IP Address</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Changes</th>
            </tr>
        </thead>

        @forelse($logs as $log)
            {{--
                Each entry gets its own <tbody> (valid HTML — a table can
                have several) so the "open" toggle below can be scoped
                with Alpine's x-data to just this row-pair, instead of
                leaking across every row in the table.
            --}}
            <tbody x-data="{ open: false }" class="divide-y divide-slate-100 dark:divide-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-b-0">
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                        <span title="{{ $log->created_at->format('M j, Y g:i:s A') }}">{{ $log->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-700 dark:text-slate-200">
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <x-badge :color="$log->actionColor()">{{ $log->actionLabel() }}</x-badge>
                    </td>
                    <td class="px-6 py-3 text-slate-600 dark:text-slate-300 max-w-md truncate" title="{{ $log->description }}">
                        {{ $log->description }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-400 dark:text-slate-500">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right">
                        @if($log->old_values || $log->new_values)
                            <button type="button" @click="open = !open" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline">
                                <span x-text="open ? 'Hide' : 'View'"></span>
                            </button>
                        @else
                            <span class="text-slate-300 dark:text-slate-600">—</span>
                        @endif
                    </td>
                </tr>

                @if($log->old_values || $log->new_values)
                    <tr x-show="open" x-cloak>
                        <td colspan="6" class="px-6 pb-4 bg-slate-50 dark:bg-slate-800/40">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-1">
                                <div>
                                    <p class="font-semibold text-slate-500 dark:text-slate-400 mb-1">Before</p>
                                    <pre class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-3 overflow-x-auto whitespace-pre-wrap break-words text-slate-600 dark:text-slate-300">{{ $log->old_values ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-500 dark:text-slate-400 mb-1">After</p>
                                    <pre class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-3 overflow-x-auto whitespace-pre-wrap break-words text-slate-600 dark:text-slate-300">{{ $log->new_values ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        @empty
            <tbody>
                <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No activity recorded yet.</td></tr>
            </tbody>
        @endforelse
    </table>
    </div>
</div>

<div class="mt-6"><x-pagination :paginator="$logs" /></div>
@endsection
