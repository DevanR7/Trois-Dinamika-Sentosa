<x-guest-layout>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold">Buat Akun Baru</h4>
                <p class="text-muted">Isi data di bawah untuk mendaftar</p>
            </div>
            
            <form method="POST" action="{{ route("register") }}">
                @csrf

                <div class="mb-3">
                    <label for="full_name" class="form-label">{{ __('Nama Lengkap') }}</label>
                    <input id="full_name" class="form-control" type="text" name="full_name" value="{{ old('full_name') }}" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">{{ __('Username') }}</label>
                    <input id="username" class="form-control" type="text" name="username" value="{{ old('username') }}" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <div class="input-group">
                        <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password"><i class="bi bi-eye-slash"></i></button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">{{ __('Konfirmasi Password') }}</label>
                    <div class="input-group">
                        <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" />
                         <button class="btn btn-outline-secondary" type="button" id="toggle-password-confirmation"><i class="bi bi-eye-slash"></i></button>
                    </div>
                    <div id="password-match-error" class="text-danger small mt-1 d-none">Password tidak cocok.</div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a class="text-decoration-none" href="{{ route("login") }}">
                        {{ __("Sudah punya akun?") }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __("Register") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIKA UNTUK HIDE/UNHIDE PASSWORD ---
    function setupPasswordToggle(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = document.getElementById(toggleId);
        if (!passwordInput || !toggleButton) return;

        const eyeIcon = toggleButton.querySelector('i');
        toggleButton.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye-slash');
            eyeIcon.classList.toggle('bi-eye');
        });
    }
    setupPasswordToggle('password', 'toggle-password');
    setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');

    // --- LOGIKA UNTUK VALIDASI KONFIRMASI PASSWORD ---
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-match-error');
    if (passwordInput && passwordConfirmationInput && passwordError) {
        function validatePasswordMatch() {
            if (passwordInput.value !== passwordConfirmationInput.value && passwordConfirmationInput.value.length > 0) {
                passwordError.classList.remove('d-none');
            } else {
                passwordError.classList.add('d-none');
            }
        }
        passwordInput.addEventListener('input', validatePasswordMatch);
        passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
    }
});
</script>
@endpush