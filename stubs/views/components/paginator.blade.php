@props(['paginator'])

@if($paginator->count > 1)
    <nav aria-label="Pagination">
        @if($paginator->prevLink !== null)
            <a href="{{ $paginator->prevLink }}">&laquo; Previous</a>
        @endif

        @foreach($paginator->links as $index => $link)
            @if($index + 1 === $paginator->currentPage)
                <span>{{ $index + 1 }}</span>
            @else
                <a href="{{ $link }}">{{ $index + 1 }}</a>
            @endif
        @endforeach

        @if($paginator->nextLink !== null)
            <a href="{{ $paginator->nextLink }}">Next &raquo;</a>
        @endif
    </nav>
@endif
