@extends('layouts.app')
@section('title', 'Lobby')
@section('content')
@php $maps = config('game.maps'); @endphp
<div class="grid2">
    <div class="panel">
        <h3>Your character</h3>
        <div class="portrait">{{ $class['emoji'] ?? '❔' }}</div>
        <div class="stat"><span>Name</span><b>{{ $ch->character_name }}</b></div>
        <div class="stat"><span>Class</span><b>{{ $ch->cha_class }}</b></div>
        <div class="stat"><span>HP</span><b>{{ $ch->max_hp }}</b></div>
        <div class="stat"><span>ATK</span><b>{{ $ch->attack }}</b></div>
        <div class="stat"><span>INT / DEX / CHA</span><b>{{ $ch->intelligence }} / {{ $ch->dexterity }} / {{ $ch->charisma }}</b></div>
        <div class="stat"><span>Attack range</span><b>{{ $class['range'] ?? 1 }} tile(s)</b></div>
        <p style="font-size:14px">{{ $class['bio'] ?? '' }}</p>
        @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
        @error('character')<div class="err">{{ $message }}</div>@enderror
        <form method="POST" action="{{ route('character.reroll') }}" style="margin:12px 0">
            @csrf
            <button class="btn" @disabled($mine)>Randomize character</button>
            @if($mine)<small>Leave your room before changing character.</small>@endif
        </form>
        <h3 style="margin-top:14px">Inventory</h3>
        @forelse($ch->items->where('quantity', '>', 0) as $ci)
            @if($ci->item)<div class="inv-slot"><span class="ic">{{ $ci->item->item_type === 'Buff' ? '🧪' : '❤️' }}</span><div><b>{{ $ci->item->item_name }}</b> ×{{ $ci->quantity }}<br>{{ $ci->item->description }}</div></div>@endif
        @empty <div class="inv-slot">Empty</div>
        @endforelse
    </div>

    <div class="panel">
        <div class="row"><h2>Game rooms</h2><a class="btn gold" href="{{ route('rooms.create') }}">New room</a></div>
        @if($mine)<div class="ok">You are already in a room. <a href="{{ route('rooms.show', $mine->gameMatch->room_id) }}">Go back to it</a></div>@endif
        @error('join')<div class="err">{{ $message }}</div>@enderror
        <form method="GET" class="row" style="margin-bottom:10px;flex-wrap:nowrap">
            <input name="q" value="{{ request('q') }}" placeholder="Search room...">
            <select name="map" style="width:140px"><option value="">All maps</option>
                @foreach($maps as $k => $m)<option value="{{ $k }}" @selected(request('map') === $k)>{{ $m['label'] }}</option>@endforeach
            </select>
            <button class="btn sm">Search</button>
        </form>
        <table>
            <tr><th>Room</th><th>Map</th><th>Players</th><th></th></tr>
            @forelse($rooms as $room)
                @php $n = $room->match->members->count(); $full = $n >= config('game.max_players'); @endphp
                <tr>
                    <td>{{ $room->room_name }} {!! $room->room_password ? '🔒' : '' !!}</td>
                    <td>{{ $maps[$room->match->map->map_name]['label'] ?? $room->match->map->map_name }}</td>
                    <td>{{ $n }}/{{ config('game.max_players') }}</td>
                    <td>
                        <form method="POST" action="{{ route('rooms.join', $room->room_id) }}" class="row" style="flex-wrap:nowrap">
                            @csrf
                            @if($room->room_password)<input name="password" placeholder="Password" style="width:110px">@endif
                            <button class="btn sm go" @disabled($full || $mine)>Join</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No open rooms yet. Create one to start playing.</td></tr>
            @endforelse
        </table>
    </div>
</div>
@endsection
