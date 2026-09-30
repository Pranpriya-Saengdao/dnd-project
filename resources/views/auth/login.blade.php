@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="center">
    <div class="ribbon">D&amp;D Web Game</div>
    <form method="POST" action="{{ route('login.post') }}" class="panel narrow">
        @csrf
        <div class="tabs">
            <label><input type="radio" name="as" value="player" {{ old('as', 'player') === 'player' ? 'checked' : '' }}>Player</label>
            <label><input type="radio" name="as" value="admin" {{ old('as') === 'admin' ? 'checked' : '' }}>Admin</label>
        </div>
        <h2>Login</h2>
        @error('username')<div class="err">{{ $message }}</div>@enderror
        <label class="f">User name <input name="username" value="{{ old('username') }}" required autofocus></label>
        <label class="f">Password <input type="password" name="password" required></label>
        <div class="row"><a class="btn" href="{{ route('register') }}">Sign up</a><button class="btn go">Confirm</button></div>
    </form>
</div>
@endsection
