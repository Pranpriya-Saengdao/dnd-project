@extends('admin.layout')
@section('admin')
<div class="row" style="justify-content:flex-start;gap:16px">
    @foreach(['Total users' => $totalUsers, 'Games played' => $totalGames, 'Playing now' => $playing] as $label => $n)
        <div class="inv-slot" style="flex-direction:column;align-items:flex-start;min-width:150px"><span>{{ $label }}</span><b style="font-size:30px">{{ $n }}</b></div>
    @endforeach
</div>
<h3 style="margin-top:16px">Top players</h3>
<table>
    <tr><th>#</th><th>Player</th><th>Wins</th><th>Total damage</th></tr>
    @forelse($top as $i => $t)<tr><td>{{ $i + 1 }}</td><td>{{ $t->username }}</td><td>{{ $t->wins }}</td><td>{{ $t->damage }}</td></tr>
    @empty<tr><td colspan="4">No finished games yet.</td></tr>@endforelse
</table>
@endsection
