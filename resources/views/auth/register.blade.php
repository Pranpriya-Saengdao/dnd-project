@extends('layouts.app')
@section('title', 'Sign up')
@section('content')
<div class="center">
    <div class="ribbon">D&amp;D Web Game</div>
    <form method="POST" action="{{ route('register.post') }}" class="panel narrow">
        @csrf
        <h2>Sign up</h2>
        <p style="margin:0 0 8px;font-size:14px">A random character will be assigned to you.</p>
        @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
        <label class="f">Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <label class="f">Password <input type="password" name="password" required minlength="6"></label>
        <label class="f">User name <input name="username" value="{{ old('username') }}" required></label>
        <div class="row"><a class="btn" href="{{ route('login') }}">Login</a><button class="btn go">Confirm</button></div>
    </form>
</div>
@endsection
