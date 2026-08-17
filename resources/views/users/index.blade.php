@extends('layouts.fuel')

@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-subtitle', 'Create staff accounts and control what they can access')

@section('header-actions')
    <a href="{{ route('users.create') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">+ New User</a>
@endsection

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
           class="w-full sm:w-72 rounded-lg border-slate-300 dark:border-slate-600 text-sm">
    <button class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Filter</button>
</form>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-800">
            <tr>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Name</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Email</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Role</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Status</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap">Last Active</th>
                <th class="px-6 py-3 font-medium whitespace-nowrap text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($users as $user)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-800 dark:text-slate-100">
                        {{ $user->name }}
                        @if($user->is(auth()->user()))
                            <span class="ml-1 text-xs text-slate-400 dark:text-slate-500">(you)</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $user->email }}</td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <x-badge :color="match($user->role) { 'superadmin' => 'purple', 'admin' => 'blue', default => 'gray' }">
                            {{ \App\Models\User::ROLES[$user->role] ?? ucfirst($user->role) }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <x-badge :color="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Active' : 'Disabled' }}</x-badge>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                        {{ $user->last_activity_at?->diffForHumans() ?? 'Never' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right space-x-2">
                        <a href="{{ route('users.edit', $user) }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline">Edit</a>
                        @unless($user->is(auth()->user()))
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" data-confirm="This will permanently delete the user account. This cannot be undone." data-confirm-type="danger" data-confirm-title="Delete this user?">
                                @csrf @method('DELETE')
                                <button class="text-red-600 dark:text-red-400 text-sm font-medium hover:underline">Delete</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6"><x-pagination :paginator="$users" /></div>
@endsection
