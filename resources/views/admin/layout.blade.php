@extends('layouts.app')
@section('title', 'Admin panel')
@section('content')
<div class="panel">
    <h2>Admin panel</h2>
    <div class="row" style="justify-content:flex-start;margin-bottom:12px">
        <a class="btn sm {{ request()->routeIs('admin.dashboard') ? 'go' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="btn sm {{ request()->routeIs('admin.users*') ? 'go' : '' }}" href="{{ route('admin.users') }}">Users</a>
        <a class="btn sm {{ request()->routeIs('admin.items*') ? 'go' : '' }}" href="{{ route('admin.items') }}">Items</a>
        <a class="btn sm {{ request()->routeIs('admin.games*') ? 'go' : '' }}" href="{{ route('admin.games') }}">Game records</a>
    </div>
    @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
    @yield('admin')
</div>
@endsection
