@extends('layouts.master')
@section('title', 'Products')
@section('content')


@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="row mt-2">
    <div class="col col-10">
        <h1>Products</h1>
    </div>
    <div class="col col-2">
        @can('add_products')
        <a href="{{ route('products_edit') }}" class="btn btn-success form-control">Add Product</a>
        @endcan
    </div>
</div>

<form>
    <div class="row">
        <div class="col col-sm-2">
            <input name="keywords" type="text" class="form-control" placeholder="Search Keywords" value="{{ request()->keywords }}" />
        </div>
        <div class="col col-sm-2">
            <input name="min_price" type="numeric" class="form-control" placeholder="Min Price" value="{{ request()->min_price }}" />
        </div>
        <div class="col col-sm-2">
            <input name="max_price" type="numeric" class="form-control" placeholder="Max Price" value="{{ request()->max_price }}" />
        </div>
        <div class="col col-sm-2">
            <select name="order_by" class="form-select">
                <option value="" {{ request()->order_by == "" ? "selected" : "" }} disabled>Order By</option>
                <option value="name" {{ request()->order_by == "name" ? "selected" : "" }}>Name</option>
                <option value="price" {{ request()->order_by == "price" ? "selected" : "" }}>Price</option>
            </select>
        </div>
        <div class="col col-sm-2">
            <select name="order_direction" class="form-select">
                <option value="" {{ request()->order_direction == "" ? "selected" : "" }} disabled>Order Direction</option>
                <option value="ASC" {{ request()->order_direction == "ASC" ? "selected" : "" }}>ASC</option>
                <option value="DESC" {{ request()->order_direction == "DESC" ? "selected" : "" }}>DESC</option>
            </select>
        </div>
        <div class="col col-sm-1">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
        <div class="col col-sm-1">
            <button type="reset" class="btn btn-danger">Reset</button>
        </div>
    </div>
</form>

@foreach($products as $product)
    <div class="card mt-2">
        <div class="card-body">
            <div class="row">
                <div class="col col-sm-12 col-lg-4">
                    <img src="{{ asset("images/$product->photo") }}" class="img-thumbnail" alt="{{ $product->name }}" width="100%">
                </div>
                <div class="col col-sm-12 col-lg-8 mt-3">
                    <div class="row mb-2">
                        <div class="col col-2">
                            @can('delete_products')
                            <a href="{{ route('products_delete', $product->id) }}" class="btn btn-danger form-control">Delete</a>
                            @endcan
                        </div>
                        <div class="col col-2">
                            @can('edit_products')
                            <a href="{{ route('products_edit', $product->id) }}" class="btn btn-warning form-control">Edit</a>
                            @endcan
                        </div>
                        <div class="col col-2">
                            @if($product->stock > 0)
                                <a href="{{ route('products_buy', $product->id) }}" class="btn btn-primary form-control">Buy</a>
                            @else
                                <button class="btn btn-secondary form-control" disabled>Out of Stock</button>
                            @endif
                        </div>
                    </div>

                    <table class="table table-striped">
                        <tr><th>Name</th><td>{{ $product->name }}</td></tr>
                        <tr><th>Price</th><td>{{ $product->price }}</td></tr>
                        <tr><th>Stock</th><td>{{ $product->stock }}</td></tr> <!-- Added Stock Row -->
                        <tr><th>Description</th><td>{{ $product->description }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
