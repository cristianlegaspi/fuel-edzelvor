@php
    $isSelf = $user->exists && $user->is(auth()->user());
    $isEdit = $user->exists;
@endphp

<div class="max-w-xl rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
        <input type="text" name="name" required value="{{ old('name', $user->name) }}"
               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-amber-500 focus:ring-amber-500">
        @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
        <input type="email" name="email" required value="{{ old('email', $user->email) }}"
               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-amber-500 focus:ring-amber-500">
        @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password @if($isEdit)<span class="text-slate-400 font-normal">(leave blank to keep current)</span>@endif</label>
            <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-amber-500 focus:ring-amber-500">
            @error('password') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }}
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-amber-500 focus:ring-amber-500">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Role</label>
        <select name="role" {{ $isSelf ? 'disabled' : 'required' }}
                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-amber-500 focus:ring-amber-500">
            @foreach(\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if($isSelf)
            <input type="hidden" name="role" value="{{ $user->role }}">
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">You can't change your own role.</p>
        @endif
        @error('role') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div>
        @if($isSelf)
            <input type="hidden" name="is_active" value="1">
            <p class="text-sm text-slate-500 dark:text-slate-400">Status: <span class="font-medium text-green-600 dark:text-green-400">Active</span></p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">You can't deactivate your own account.</p>
        @else
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                Active (unchecking blocks this user from logging in)
            </label>
        @endif
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
            {{ $isEdit ? 'Save Changes' : 'Create User' }}
        </button>
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-500 dark:text-slate-400 hover:underline">Cancel</a>
    </div>
</div>
