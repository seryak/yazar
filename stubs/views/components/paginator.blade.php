@props(['paginator'])

@if($paginator->count > 1)
    <nav aria-label="Pagination">
        @if($paginator->prevLink !== null)
            <a href="{{ $paginator->prevLink }}" rel="prev">&laquo; Previous</a>
        @endif

        @foreach($paginator->window() as $page)
            @if($page === null)
                <span aria-hidden="true">&hellip;</span>
            @elseif($page === $paginator->currentPage)
                <span aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if($paginator->nextLink !== null)
            <a href="{{ $paginator->nextLink }}" rel="next">Next &raquo;</a>
        @endif
    </nav>
@endif
