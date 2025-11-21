@extends('layouts.app') 

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Manajemen User</h3>
            <p class="text-muted small mb-0">Kelola staf, admin, dan hak akses.</p>
        </div>
        <div>
            @if(request('status') === 'deleted')
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary shadow-sm btn-sm me-2">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            @else
                <a href="{{ route('users.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary shadow-sm btn-sm me-2">
                    <i class="bi bi-archive me-1"></i>Lihat Arsip
                </a>
                @can('manage-users')
                <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Tambah User
                </a>
                @endcan
            @endif
        </div>
    </div>
    
    {{-- ACCORDION VIEW --}}
    <div class="accordion shadow-sm" id="usersAccordion">
        
        @forelse ($users as $user)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $user->user_id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse-{{ $user->user_id }}" aria-expanded="false" 
                        aria-controls="collapse-{{ $user->user_id }}">
                    
                    <div class="d-flex justify-content-between w-100 align-items-center pe-5">
                        
                        {{-- Kiri: Nama & Username --}}
                        <div class="d-flex flex-column text-start">
                            <strong class="fs-5 text-dark">{{ $user->full_name }}</strong>
                            <span class="text-muted small">{{ $user->username }}</span>
                        </div>
                        
                        {{-- Kanan: Role & Status --}}
                        <div class="text-end">
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill me-2">
                                {{ $user->getRoleNames()->first() ?? 'N/A' }}
                            </span>

                            @if($user->trashed())
                                <span class="badge bg-danger rounded-pill">Diarsipkan</span>
                            @elseif($user->is_approved)
                                <span class="badge bg-success rounded-pill">Aktif</span>
                            @else
                                <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                            @endif
                        </div>
                    </div>
                </button>
            </h2>

            {{-- TAMPILAN TERBUKA (EXPANDED) --}}
            <div id="collapse-{{ $user->user_id }}" class="accordion-collapse collapse" 
                 aria-labelledby="heading-{{ $user->user_id }}" data-bs-parent="#usersAccordion">
                <div class="accordion-body bg-light bg-opacity-25">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Email:</strong> {{ $user->email ?? '-' }}</p>
                            <p class="mb-1"><strong>NIK:</strong> {{ $user->nik ?? '-' }}</p>
                            <p class="mb-1"><strong>Telepon:</strong> {{ $user->phone_number ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Kode Sales:</strong> <span class="badge bg-secondary">{{ $user->sales_code ?? '-' }}</span></p>
                            <p class="mb-1"><strong>Alamat:</strong> {{ $user->address ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    {{-- Tombol Aksi --}}
                    <strong>Tindakan:</strong>
                    <div class="d-flex justify-content-start flex-wrap gap-2 mt-2">
                        @if($user->trashed())
                            @can('manage-users')
                            <form action="{{ route('users.restore', $user->user_id) }}" method="POST" class="form-restore d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success" data-name="{{ $user->full_name }}"><i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan</button>
                            </form>
                            @endcan
                        @else
                            @can('manage-users')
                                {{-- Tombol Setujui --}}
                                @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                                <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-approve d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success" title="Setujui User" data-name="{{ $user->full_name }}"><i class="bi bi-check-circle me-1"></i> Setujui</button>
                                </form>
                                @endif
                                
                                {{-- Tombol Edit --}}
                                <a href="{{ route('users.edit', $user->user_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square me-1"></i> Edit</a>
                                
                                {{-- Tombol Hapus/Arsipkan (User tidak boleh menghapus dirinya sendiri) --}}
                                @if(Auth::id() !== $user->user_id)
                                <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-delete d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Arsipkan" data-name="{{ $user->full_name }}"><i class="bi bi-archive me-1"></i> Arsipkan</button>
                                </form>
                                @endif
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        {{-- Tampilan jika tidak ada user --}}
        <div class="alert alert-info text-center py-5 my-3">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
            Tidak ada data user.
        </div>
        @endforelse

    </div>
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection