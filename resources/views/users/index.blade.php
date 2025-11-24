@extends('layouts.app') 

@section('title', 'Manajemen User')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen User</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola staf, admin, dan hak akses sistem.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            @if(request('status') === 'deleted')
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            @else
                <a href="{{ route('users.index', ['status' => 'deleted']) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">archive</i> Lihat Arsip
                </a>
                @can('manage-users')
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">person_add</i> Tambah User
                </a>
                @endcan
            @endif
        </div>
    </div>
    
    {{-- USER GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($users as $user)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col">
            
            {{-- Card Header --}}
            <div class="p-5 border-b border-gray-100 flex items-start justify-between">
                <div class="flex items-center gap-3">
                    {{-- Avatar Placeholder --}}
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-gray-900 leading-tight">{{ $user->full_name }}</h4>
                        <span class="text-xs text-gray-500 block">{{ $user->username }}</span>
                    </div>
                </div>
                {{-- Badge Role --}}
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100 capitalize">
                    {{ $user->getRoleNames()->first() ?? 'N/A' }}
                </span>
            </div>

            {{-- Card Body --}}
            <div class="p-5 flex-grow text-sm text-gray-600 space-y-2">
                <div class="flex items-center gap-2">
                    <i class="material-icons text-base text-gray-400">email</i>
                    <span class="truncate">{{ $user->email }}</span>
                </div>
                @if($user->phone_number)
                <div class="flex items-center gap-2">
                    <i class="material-icons text-base text-gray-400">phone</i>
                    <span>{{ $user->phone_number }}</span>
                </div>
                @endif
                @if($user->sales_code)
                <div class="flex items-center gap-2">
                    <i class="material-icons text-base text-gray-400">badge</i>
                    <span>Sales: <span class="font-mono font-bold text-gray-800">{{ $user->sales_code }}</span></span>
                </div>
                @endif
                
                {{-- Status Badge --}}
                <div class="pt-2">
                    @if($user->trashed())
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                            <i class="material-icons text-[10px] mr-1">archive</i> Diarsipkan
                        </span>
                    @elseif($user->is_approved)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            <i class="material-icons text-[10px] mr-1">check_circle</i> Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="material-icons text-[10px] mr-1">hourglass_empty</i> Pending
                        </span>
                    @endif
                </div>
            </div>

            {{-- Card Footer (Actions) --}}
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                @if($user->trashed())
                    @can('manage-users')
                    <form action="{{ route('users.restore', $user->user_id) }}" method="POST" class="form-restore">
                        @csrf @method('PATCH')
                        <button type="submit" class="inline-flex items-center px-2 py-1 bg-white border border-green-300 rounded text-xs font-medium text-green-700 hover:bg-green-50 transition" data-name="{{ $user->full_name }}">
                            <i class="material-icons text-sm mr-1">restore</i> Pulihkan
                        </button>
                    </form>
                    @endcan
                @else
                    @can('manage-users')
                        {{-- Approve --}}
                        @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                        <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-approve">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-600 border border-transparent rounded text-xs font-medium text-white hover:bg-green-700 transition" title="Setujui" data-name="{{ $user->full_name }}">
                                <i class="material-icons text-sm">check</i>
                            </button>
                        </form>
                        @endif
                        
                        {{-- Edit --}}
                        <a href="{{ route('users.edit', $user->user_id) }}" class="inline-flex items-center px-2 py-1 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50 transition" title="Edit">
                            <i class="material-icons text-sm">edit</i>
                        </a>
                        
                        {{-- Delete --}}
                        @if(Auth::id() !== $user->user_id)
                        <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-white border border-red-200 rounded text-xs font-medium text-red-600 hover:bg-red-50 transition" title="Arsipkan" data-name="{{ $user->full_name }}">
                                <i class="material-icons text-sm">archive</i>
                            </button>
                        </form>
                        @endif
                    @endcan
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="flex flex-col items-center justify-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-300 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                    <i class="material-icons text-4xl text-gray-400">people_outline</i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak Ada Data User</h3>
                <p class="text-gray-500 max-w-sm mt-1">Belum ada user yang terdaftar atau sesuai filter.</p>
            </div>
        </div>
        @endforelse
    </div>
    
    <div class="mt-6">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection