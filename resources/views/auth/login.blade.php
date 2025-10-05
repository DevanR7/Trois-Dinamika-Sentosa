<x-guest-layout>
    <form method="POST" action="{{ route("login") }}">
        @csrf

        <div class="text-center mb-4">
            <h4 class="fw-bold">Selamat Datang Kembali</h4>
            <p class="text-muted">Silakan masuk untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger text-center p-2 mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-3">
            <label for="username" class="form-label">
                {{ __("Username") }}
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-person-fill"></i>
                </span>
                <input
                    id="username"
                    class="form-control"
                    type="text"
                    name="username"
                    placeholder="Masukkan username Anda"
                    value="{{ old("username") }}"
                    required
                    autofocus
                />
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">
                {{ __("Password") }}
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-lock-fill"></i>
                </span>
                <input
                    id="password"
                    class="form-control"
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                />
                <button
                    class="btn btn-outline-secondary"
                    type="button"
                    id="togglePassword"
                >
                    <i class="bi bi-eye-slash-fill"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="form-check-input"
                    name="remember"
                />
                <label for="remember_me" class="form-check-label">
                    {{ __("Remember me") }}
                </label>
            </div>
            @if (Route::has("password.request"))
                <a
                    class="text-decoration-none small"
                    href="{{ route("password.request") }}"
                >
                    {{ __("Lupa password?") }}
                </a>
            @endif
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __("Log in") }}
            </button>
        </div>
    </form>
</x-guest-layout>

{{-- LETAKKAN SCRIPT LANGSUNG DI SINI --}}
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function () {
        // ganti tipe input
        const type =
            password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        // ganti ikon
        icon.classList.toggle('bi-eye-slash-fill');
        icon.classList.toggle('bi-eye-fill');
    });
</script>
