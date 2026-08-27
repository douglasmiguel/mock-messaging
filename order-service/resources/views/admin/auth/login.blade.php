@extends('layouts.admin')

@section('content')
    <section class="login-card card">
        <div class="login-header">
            <p class="eyebrow">Local admin</p>
            <h1>Order Service</h1>
            <p class="muted">Sign in to browse and inspect local orders.</p>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}" class="login-form">
            @csrf
            <label for="username">Username or email</label>
            <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            @error('username') <p class="error">{{ $message }}</p> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <button type="submit">Sign in</button>
        </form>
    </section>
@endsection

@push('styles')
    <style>
        .login-card { max-width: 430px; margin: 10vh auto 0; padding: 30px; }
        .login-header h1 { margin: 4px 0 8px; font-size: 28px; letter-spacing: -0.6px; }
        .login-header p { margin: 0; }
        .eyebrow { color: #4c1d95; font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
        .login-form { display: grid; gap: 9px; margin-top: 28px; }
        .login-form label { margin-top: 8px; color: #344054; font-size: 14px; font-weight: 650; }
        .login-form button { margin-top: 16px; }
    </style>
@endpush
