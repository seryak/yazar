@props(['paginator'])

@if($paginator->count > 1)
    <nav aria-label="Pagination">
        @if($paginator->prevLink !== null)
            <a href="{{ $paginator->prevLink }}" rel="prev" aria-label="Previous page">
                <span aria-hidden="true">&laquo;</span>
                <span style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">Previous</span>
            </a>
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
            <a href="{{ $paginator->nextLink }}" rel="next" aria-label="Next page">
                <span aria-hidden="true">&raquo;</span>
                <span style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">Next</span>
            </a>
        @endif
    </nav>
@endif
