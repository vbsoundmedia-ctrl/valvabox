@extends('layouts.app')
@section('title', 'Videos')
@section('content')
<div class="top"><div><h1>Videos</h1></div></div>
<div class="tabs">@foreach (['in_review' => 'In review', 'approved' => 'Approved', 'delivered' => 'Delivered', 'live' => 'Live', 'rejected' => 'Needs changes', 'all' => 'All'] as $k => $v)<a class="{{ $status === $k ? 'on' : '' }}" href="?status={{ $k }}">{{ $v }}</a>@endforeach</div>
<div class="card">
    @if ($videos->isEmpty())<div class="empty">Nothing here.</div>@else
    <div class="table-wrap"><table><tr><th>Video</th><th>Account</th><th>Release date</th><th>Status</th></tr>
        @foreach ($videos as $v)<tr><td><a href="{{ route('admin.videos.show', $v) }}"><b>{{ $v->title }}</b></a><br><span class="small muted">{{ $v->artist }}</span></td><td class="small">{{ $v->user->email }}</td><td>{{ $v->release_date?->format('j M Y') }}</td><td><span class="badge {{ status_class($v->status) }}">{{ $v->statusLabel() }}</span></td></tr>@endforeach
    </table></div>
    @include('partials.pagination', ['paginator' => $videos])
    @endif
</div>
@endsection
