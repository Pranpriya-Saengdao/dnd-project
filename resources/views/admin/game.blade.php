@extends('admin.layout')
@section('admin')
<div class="row"><h3>Game #{{ $match->match_id }} — {{ $match->room?->room_name }}</h3><a class="btn sm" href="{{ route('admin.games') }}">Back</a></div>
<table>
    <tr><th>Player</th><th>Class</th><th>Team</th><th>HP</th><th>Damage dealt</th><th>Result</th></tr>
    @foreach($match->members as $m)
        @php $r = $results[$m->member_id] ?? null; @endphp
        <tr><td>{{ $m->user?->username }}</td><td>{{ $m->character?->cha_class }}</td><td>{{ $teams[$m->team_id] ?? '-' }}</td>
            <td>{{ $m->hp }}/{{ $m->character?->max_hp }}</td><td>{{ $r?->total_damage ?? '-' }}</td>
            <td>{{ $r ? ($r->result ? 'Win' : 'Lose') : '-' }}</td></tr>
    @endforeach
</table>
<h3 style="margin-top:14px">Combat records</h3>
<table>
    <tr><th>Turn</th><th>Attacker</th><th>Target</th><th>D10</th><th>Damage</th></tr>
    @forelse($log as $l)
        <tr><td>{{ $l->turn_number }}</td><td>{{ $l->attacker?->user?->username }}</td><td>{{ $l->target?->user?->username }}</td>
            <td>{{ $l->diceRoll?->dice_result }}</td><td>{{ $l->damage_dealt }}</td></tr>
    @empty<tr><td colspan="5">No attacks were made.</td></tr>@endforelse
</table>
@endsection
