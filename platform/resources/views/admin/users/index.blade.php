@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="top"><div><h1>Artists &amp; users</h1></div>
    <form method="get" class="row"><input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email or phone" style="width:240px"><button class="btn btn-light">Search</button></form></div>
<div class="card"><div class="table-wrap"><table>
    <tr><th>Name</th><th>Email / phone</th><th>Plan</th><th>Releases</th><th>Joined</th><th>Status</th></tr>
    @foreach ($users as $u)
        <tr><td><a href="{{ route('admin.users.show', $u) }}"><b>{{ $u->displayName() }}</b></a>@if ($u->isAdmin()) <span class="badge muted">admin</span>@endif<br><span class="small muted">{{ $u->name }}</span></td>
            <td class="small">{{ $u->email }}<br>{{ $u->phone }}</td><td>{{ $u->currentPlan()->name }}</td><td>{{ $u->releases_count }}</td>
            <td class="small nowrap">{{ $u->created_at->format('j M Y') }}</td><td><span class="badge {{ status_class($u->status) }}">{{ $u->status }}</span></td></tr>
    @endforeach
</table></div>@include('partials.pagination', ['paginator' => $users])</div>
@endsection
