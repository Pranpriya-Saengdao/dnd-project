@extends('layouts.app')
@section('title', 'Create room')
@section('content')
<div class="center">
<form method="POST" action="{{ route('rooms.store') }}" class="panel" style="width:min(640px,100%)">
    @csrf
    <div class="row"><h2>Create game room</h2><a class="btn sm" href="{{ route('lobby') }}">Back</a></div>
    @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
    <label class="f">Room name <input name="room_name" value="{{ old('room_name') }}" required maxlength="45"></label>
    <label class="f">Password <input name="room_password" value="{{ old('room_password') }}" maxlength="50" placeholder="optional"></label>
    <div style="margin:12px 0 6px">Map</div>
    <div class="maps">
        @foreach(config('game.maps') as $key => $map)
            <label>
                <input type="radio" name="map" value="{{ $key }}" {{ old('map', 'forest') === $key ? 'checked' : '' }}>
                <span class="mini"><span class="mini-grid">
                    @foreach($map['grid'] as $row)@foreach(str_split($row) as $c)<i class="{{ ['#' => 'w', 'b' => 'b', 'c' => 'c'][$c] ?? '' }}"></i>@endforeach @endforeach
                </span></span>{{ $map['label'] }}
            </label>
        @endforeach
    </div>
    <div style="margin:14px 0 6px">Turn time limit</div>
    <div class="radio-row">
        @foreach([15, 30, 60] as $t)<label><input type="radio" name="turn_time" value="{{ $t }}" {{ (int) old('turn_time', 30) === $t ? 'checked' : '' }}>{{ $t }} s</label>@endforeach
    </div>
    <div class="row" style="margin-top:18px;justify-content:flex-end"><button class="btn go">Create</button></div>
</form>
</div>
@endsection
