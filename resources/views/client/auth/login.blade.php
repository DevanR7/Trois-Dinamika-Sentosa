@extends('layouts.guest')

@section('content')
    <form method="POST" action="{{ route('client.login') }}">
        @csrf
        <div class="text-center mb-4">
            <h4 class="fw-bold">Client Portal Login</h4>
            <p class="text-muted">Selamat datang! Silakan masuk.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger text-center p-2 mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-control" type="password" name="password" required>
        </div>

        <div class="mb-3 form-check">
            <input id="remember" type="checkbox" class="form-check-input" name="remember">
            <label for="remember" class="form-check-label">Ingat saya</label>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Log in</button>
        </div>
    </form>
@endsection