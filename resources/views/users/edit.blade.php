@extends('layouts.fuel')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', $user->name)

@section('content')
<form method="POST" action="{{ route('users.update', $user) }}"
      data-confirm="Save changes to this user?" data-confirm-title="Save changes">
    @csrf
    @method('PUT')
    @include('users._form')
</form>
@endsection
