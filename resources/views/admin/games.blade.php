@extends('admin.layout')
@section('admin')
<table>
    <tr><th>Match</th><th>Room</th><th>Players</th><th>Status</th><th>Started</th><th></th></tr>
    @forelse($matches as $m)
        <tr>
            <td>#{{ $m->match_id }}</td><td>{{ $m->room?->room_name }}</td>
            <td>{{ $m->members->map(fn ($x) => $x->user?->username)->implode(', ') }}</td>
            <td>{{ $m->room?->status }}</td><td>{{ $m->started_at?->format('Y-m-d H:i') }}</td>
            <td><a class="btn sm" href="{{ route('admin.games.show', $m->match_id) }}">View</a></td>
        </tr>
    @empty<tr><td colspan="6">No games yet.</td></tr>@endforelse
</table>
@endsection
