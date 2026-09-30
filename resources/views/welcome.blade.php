@extends('layouts.app')
@section('content')
<div class="center">
    <h1 class="hero-title">D&amp;D<span>WEB GAME</span></h1>
    <a class="btn gold" href="{{ auth()->check() ? route('lobby') : route('login') }}">START</a>
</div>
@endsection
