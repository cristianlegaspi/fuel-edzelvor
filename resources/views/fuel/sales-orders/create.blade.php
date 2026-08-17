@extends('layouts.fuel')

@section('title', 'New Supplier Order')
@section('page-title', 'New Supplier Order')
@section('page-subtitle', 'Record fuel purchased from a supplier')

@section('content')
<form method="POST" action="{{ route('fuel-sales-orders.store') }}"
      data-confirm="Save this new supplier order?" data-confirm-title="Save order">
    @csrf
    @include('fuel.sales-orders._form')
</form>
@endsection
