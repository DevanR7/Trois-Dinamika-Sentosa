@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white"><h4 class="mb-0">Tambah Satuan Baru</h4></div>
                <div class="card-body p-4">
                    <form action="{{ route('units.store') }}" method="POST">
                        @csrf
                        @include('units._form')
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('units.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection