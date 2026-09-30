@extends('admin.layout')
@section('admin')
<table>
    <tr><th>Username</th><th>Email</th><th>Role</th><th>Character</th><th>Status</th><th></th></tr>
    @foreach($users as $u)
        <tr>
            <form method="POST" action="{{ route('admin.users.update', $u->user_id) }}">@csrf @method('PUT')
                <td><input name="username" value="{{ $u->username }}"></td>
                <td><input name="email" value="{{ $u->email }}"></td>
                <td><select name="role"><option value="player" @selected($u->role === 'player')>player</option><option value="admin" @selected($u->role === 'admin')>admin</option></select></td>
                <td>{{ $u->character?->cha_class }}</td>
                <td>{{ $u->trashed() ? 'Deleted' : 'Active' }}</td>
                <td><button class="btn sm go">Save</button></td>
            </form>
            <td>
                @if($u->trashed())
                    <form method="POST" action="{{ route('admin.users.restore', $u->user_id) }}">@csrf<button class="btn sm">Restore</button></form>
                @elseif($u->user_id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.delete', $u->user_id) }}" onsubmit="return confirm('Delete {{ $u->username }}?')">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
                @endif
            </td>
        </tr>
    @endforeach
</table>
@endsection
