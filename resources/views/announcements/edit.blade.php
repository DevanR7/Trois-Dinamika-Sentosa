@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                 <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Pengumuman</h4>
                     <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('announcements.update', $announcement->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        {{-- Include form partial, lewatkan semua variabel yg dibutuhkan --}}
                        @include('announcements._form', [
                            'announcement' => $announcement, 
                            'clients' => $clients, 
                            'selectedClientIds' => $selectedClientIds
                        ]) 
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('announcements.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Update Pengumuman</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection