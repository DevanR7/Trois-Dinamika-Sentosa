@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-3">
        
        {{-- MOBILE VIEW: Simple Previous & Next --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 cursor-default rounded-lg dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-indigo-600 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
                    Previous
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-indigo-600 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
                    Next
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 cursor-default rounded-lg dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                    Next
                </span>
            @endif
        </div>

        {{-- DESKTOP VIEW: Full Info & Numbers --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            
            {{-- Info Text --}}
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan
                    <span class="font-bold text-slate-700 dark:text-white">{{ $paginator->firstItem() }}</span>
                    sampai
                    <span class="font-bold text-slate-700 dark:text-white">{{ $paginator->lastItem() }}</span>
                    dari
                    <span class="font-bold text-slate-700 dark:text-white">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            {{-- Number Links --}}
            <div>
                <span class="relative z-0 inline-flex items-center gap-1 shadow-sm">
                    
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center justify-center w-8 h-8 p-0 text-sm font-medium text-slate-300 bg-transparent cursor-default rounded-lg dark:text-slate-600">
                                <i class="material-icons text-[18px]">chevron_left</i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center w-8 h-8 p-0 text-sm font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white">
                            <i class="material-icons text-[18px]">chevron_left</i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-slate-400 bg-transparent cursor-default">
                                    {{ $element }}
                                </span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-white bg-indigo-600 border border-indigo-600 rounded-lg shadow-md cursor-default">
                                            {{ $page }}
                                        </span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center w-8 h-8 p-0 text-sm font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white">
                            <i class="material-icons text-[18px]">chevron_right</i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center justify-center w-8 h-8 p-0 text-sm font-medium text-slate-300 bg-transparent cursor-default rounded-lg dark:text-slate-600">
                                <i class="material-icons text-[18px]">chevron_right</i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif