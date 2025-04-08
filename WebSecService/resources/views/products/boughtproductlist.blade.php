@extends('layouts.master')
@section('title', 'Bought Products')
@section('content')
<div class="row mt-2">
    <div class="col col-12">
        <h1>Bought Products</h1>
    </div>
</div>

<div class="card mt-2">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User Name</th>
                    <th>Product Name</th>
                    <th>Purchased At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($boughtProducts as $boughtProduct)
                <tr>
                    <td>{{ $boughtProduct->id }}</td>
                    <td>{{ $boughtProduct->user->name }}</td>
                    <td>{{ $boughtProduct->product->name }}</td>
                    <td>{{ $boughtProduct->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
