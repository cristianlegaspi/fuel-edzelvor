@extends('layouts.fuel')

@section('title', 'New User')
@section('page-title', 'New User')
@section('page-subtitle', 'Create a login and assign a role')

@section('content')
<form method="POST" action="{{ route('users.store') }}"
      data-confirm="Create this user account?" data-confirm-title="Create user">
    @csrf
    @include('users._form')
</form>
@endsection
