@if ($paginator->hasPages())
<nav class="pagination">
    @if (! $paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}">← Prev</a>@endif
    <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">Next →</a>@endif
</nav>
@endif
