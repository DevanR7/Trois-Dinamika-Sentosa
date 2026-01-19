@props([
    'view' => null,   // URL untuk view
    'edit' => null,   // URL untuk edit
    'delete' => null, // URL untuk delete (Route)
    'message' => 'Data yang dihapus tidak dapat dikembalikan.' // Custom pesan delete
])

<div class="flex items-center gap-2 justify-end">
    
    {{-- 1. Tombol VIEW --}}
    @if($view)
    <a href="{{ $view }}" class="btn-action btn-action-view" title="Lihat Detail">
        <i class="material-icons">visibility</i>
    </a>
    @endif

    {{-- 2. Tombol EDIT --}}
    @if($edit)
    <a href="{{ $edit }}" class="btn-action btn-action-edit" title="Edit Data">
        <i class="material-icons">edit</i>
    </a>
    @endif

    {{-- 3. Tombol DELETE --}}
    @if($delete)
    <form action="{{ $delete }}" method="POST" class="inline-block m-0 p-0">
        @csrf
        @method('DELETE')
        <button type="submit" 
                class="btn-action btn-action-delete" 
                title="Hapus Data"
                data-confirm-delete="true"
                data-message="{{ $message }}">
            <i class="material-icons">delete_outline</i>
        </button>
    </form>
    @endif

    {{-- Slot tambahan jika ada tombol custom lain --}}
    {{ $slot }}
</div>