@extends('layouts.master')
@section('title', 'Add User')
@section('content')
<div class="row mt-2">
    <div class="col col-12">
        <h1>Add User</h1>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('users_store') }}">
    @csrf
    <div class="row mt-3">
        <div class="col col-sm-6">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="col col-sm-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col col-sm-6">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="col col-sm-6">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col col-sm-6">
            <label for="credit" class="form-label">Credit</label>
            <input type="number" name="credit" id="credit" class="form-control" value="{{ old('credit', 5000) }}" required>
        </div>
        <div class="col col-sm-6">
            <label for="role" class="form-label">Role</label>
            <select name="role" id="role" class="form-control" required>
                @foreach($roles as $role)
                    @if(auth()->user()->hasRole('Employee') && $role->name !== 'Customer')
                        @continue
                    @endif
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col col-12">
            <button type="submit" class="btn btn-success">Add User</button>
            <a href="{{ route('users') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>

@endsection
