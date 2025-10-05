@extends("layouts.app")

@section("content")
    <div class="container-fluid">
        <h2 class="fw-bold mb-4">Edit User</h2>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Form Edit User</h4>
                    </div>
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form
                            action="{{ route("users.update", $user->user_id) }}"
                            method="POST"
                        >
                            @csrf
                            @method("PUT")
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label
                                        for="full_name"
                                        class="form-label fw-semibold"
                                    >
                                        Nama Lengkap
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="full_name"
                                        name="full_name"
                                        value="{{ old("full_name", $user->full_name) }}"
                                        required
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label
                                        for="username"
                                        class="form-label fw-semibold"
                                    >
                                        Username
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="username"
                                        name="username"
                                        value="{{ old("username", $user->username) }}"
                                        required
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label
                                        for="email"
                                        class="form-label fw-semibold"
                                    >
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="{{ old("email", $user->email) }}"
                                        required
                                    />
                                </div>
                                <div class="col-md-12">
                                    <label
                                        for="password"
                                        class="form-label fw-semibold"
                                    >
                                        Password
                                    </label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password"
                                        name="password"
                                    />
                                    <small class="form-text text-muted">
                                        Kosongkan jika tidak ingin mengubah
                                        password.
                                    </small>
                                </div>
                                <div class="col-md-12">
                                    <label
                                        for="role"
                                        class="form-label fw-semibold"
                                    >
                                        Role
                                    </label>
                                    <select
                                        class="form-select"
                                        id="role"
                                        name="role"
                                        required
                                    >
                                        <option
                                            value="admin"
                                            {{ old("role", $user->role) == "admin" ? "selected" : "" }}
                                        >
                                            Admin
                                        </option>
                                        <option
                                            value="sales"
                                            {{ old("role", $user->role) == "sales" ? "selected" : "" }}
                                        >
                                            Sales
                                        </option>
                                        <option
                                            value="manajemen"
                                            {{ old("role", $user->role) == "manajemen" ? "selected" : "" }}
                                        >
                                            Manajemen
                                        </option>
                                        <option
                                            value="kasir"
                                            {{ old("role", $user->role) == "kasir" ? "selected" : "" }}
                                        >
                                            Kasir
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a
                                    href="{{ route("users.index") }}"
                                    class="btn btn-secondary me-2"
                                >
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-success">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
