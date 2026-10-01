@if (session('success'))
    <div class="alert alert-ok">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-bad">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </div>
@endif
