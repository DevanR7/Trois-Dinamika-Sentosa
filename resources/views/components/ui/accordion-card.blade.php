@props(['id'])

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden transition-all hover:shadow-md mb-4">
    
    {{-- HEADER (Selalu Muncul) --}}
    <div class="p-4 cursor-pointer flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-slate-50/50 transition-colors relative z-10 bg-white" 
         onclick="window.toggleAccordion('{{ $id }}')">
        
        {{-- Slot Header Kiri & Kanan --}}
        {{ $header }}

        {{-- Icon Panah Otomatis --}}
        <div class="absolute right-4 top-4 md:static">
            <i class="material-icons text-slate-400 transition-transform duration-300" id="icon-{{ $id }}">expand_more</i>
        </div>
    </div>

    {{-- BODY (Tersembunyi dengan Animasi) --}}
    <div id="wrapper-{{ $id }}" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out bg-slate-50/50 border-t border-slate-100">
        <div class="overflow-hidden">
            <div class="p-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>