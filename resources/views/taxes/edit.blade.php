@extends("layouts.app")

@section("content")
    <div class="container-fluid">
        <h2 class="fw-bold mb-4">Edit Tarif Pajak</h2>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form
                            action="{{ route("taxes.update", $tax->id) }}"
                            method="POST"
                        >
                            @csrf
                            @method("PUT")
                            <div class="mb-3">
                                <label
                                    for="name"
                                    class="form-label fw-semibold"
                                >
                                    Nama Pajak
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="name"
                                    name="name"
                                    value="{{ old("name", $tax->name) }}"
                                    required
                                />
                            </div>
                            <div class="mb-3">
                                <label
                                    for="rate"
                                    class="form-label fw-semibold"
                                >
                                    Tarif (%)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    id="rate"
                                    name="rate"
                                    value="{{ old("rate", $tax->rate) }}"
                                    required
                                />
                            </div>
                            <div class="mb-3 form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    {{ old("is_active", $tax->is_active) ? "checked" : "" }}
                                />
                                <label class="form-check-label" for="is_active">
                                    Jadikan tarif ini aktif?
                                </label>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <a
                                    href="{{ route("taxes.index") }}"
                                    class="btn btn-secondary me-2"
                                >
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-success">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
