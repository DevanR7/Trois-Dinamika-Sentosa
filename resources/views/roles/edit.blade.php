@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white"><h4 class="mb-0">Edit Role: {{ Str::title($role->name) }}</h4></div>
                <div class="card-body p-4">
                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('roles._form')
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Update Role</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection