@extends('layouts.fuel')

@section('title', 'New Customer Purchase')
@section('page-title', 'New Customer Purchase')
@section('page-subtitle', 'Sell fuel from an existing supplier order')

@section('content')
<form method="POST" action="{{ route('fuel-customer-purchases.store') }}"
      data-confirm="Save this new customer purchase?" data-confirm-title="Save purchase">
    @csrf
    @include('fuel.customer-purchases._form')
</form>
@endsection
